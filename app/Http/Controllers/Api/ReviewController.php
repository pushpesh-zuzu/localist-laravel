<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\{
    Auth, Hash, DB , Mail, Validator
};
use Illuminate\Validation\Rule;
use App\Helpers\CustomHelper;

use App\Models\User;
use App\Models\UserService;
use App\Models\UserServiceLocation;
use App\Models\Category;
use App\Models\LeadRequest;
use App\Models\Review;

class ReviewController extends Controller{
    
    public function getCustomerLink(Request $request){
        $user_id = $request->user_id;
        $uuid = User::where('id',$user_id)->value('uuid');
        $url = 'https://locallists-react.vercel.app/review/'.$uuid;
        return $this->sendResponse('Customer review link',str_replace('/admin','',$url));
    }

    public function submitReview(Request $request){
        $validator = Validator::make($request->all(), [
            'uuid' => 'required|exists:users,uuid',
            'name' => 'required',
            'email' => 'required',
            'review' => 'required',
            'ratings' => 'required|numeric|min:0|max:5',
          ], [
            'uuid.required' => 'User identification is required.'
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }
        
        $uuid = $request->uuid;
        $user_id = User::where('uuid',$uuid)->value('id');
        $data['user_id'] = $user_id;
        $data['name'] = $request->name;
        $data['email'] = $request->email;
        $data['review'] = $request->review;
        $data['ratings'] = $request->ratings;
        $data['created_at'] = date('y-m-d H:i:s');
        $data['updated_at'] = date('y-m-d H:i:s');
        $aid = Review::insertGetId($data);
        if($aid){
            $avg_rating = Review::avg('ratings');
            $data2['avg_rating'] = number_format($avg_rating, 1);;
            $data2['updated_at'] = date('y-m-d H:i:s');
            User::where('id',$user_id)->update($data2);
            return $this->sendResponse('Review submitted successfully!');
        }
        return $this->sendError('Something went wrong, try again!');
        
    }

    public function getReviews(Request $request, $user_id){
        $list = Review::where('user_id',$user_id)->get();
        return $this->sendResponse('Reviews list',$list);
    }
}