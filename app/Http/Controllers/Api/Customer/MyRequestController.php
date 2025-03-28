<?php

namespace App\Http\Controllers\Api\Customer;

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

class MyRequestController extends Controller
{
    public function test(){
        return "hello world";
    }

    public function getSubmittedRequestList(Request $request){
        $user_id = $request->user_id;

        $list = LeadRequest::where('customer_id',$user_id)->get();

        return $this->sendResponse('Submitted Quotes',$list);

    }

    public function getSubmittedRequestInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'request_id' => 'required|integer|exists:lead_requests,id',
          ], [
            'image_file.required' => 'Location Postcode is required.'
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        $info = LeadRequest::where('id',$request->request_id)->get();
        return $this->sendResponse('Quotation Information',$info);

    }

    public function createNewRequest(Request $request){
        $user_id = $request->user_id;

        $validator = Validator::make($request->all(), [
            'service_id' => 'required|integer|exists:categories,id',
            'postcode' => 'required',
            'questions' => 'required',
            'phone' => 'required'
          ], [
            'postcode.required' => 'Location Postcode is required.',
            'service_id.exists' => 'Provided service id does not exists.'
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        $data['customer_id'] = $user_id;
        $data['service_id'] = $request->service_id;
        $data['postcode'] = $request->postcode;
        $data['questions'] = $request->questions;
        $data['phone'] = $request->phone;

        $data['recevive_online'] = !empty($request->recevive_online)? $request->recevive_online : '0';
        $data['created_at'] = date('y-m-d H:i:s');
        $data['updated_at'] = date('y-m-d H:i:s');

        $sId = LeadRequest::insertGetId($data);

        if($sId){
            $rel['request_id'] = $sId;
            return $this->sendResponse('Submitted Quotes',$rel);
        }
        return $this->sendError('Something went wrong, try again!');
    }

    public function addImageToSubmittedRequest(Request $request){
        $user_id = $request->user_id;

        $validator = Validator::make($request->all(), [
            'request_id' => 'required|integer|exists:lead_requests,id',
            'image_file' => 'required|mimes:jpeg,jpg,png',
          ], [
            'image_file.required' => 'Image is required.'
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        if($request->hasfile('image_file')){

            $dir = 'public/images/customer/leads';
            $single_img=$request->file('image_file');
            $file_name = "img_" .time() ."." .$single_img->getClientOriginalExtension();
            $single_img->move($dir, $file_name);

            $prevImages = LeadRequest::where('id',$request->request_id)->value('images');
            $prevImages .= !empty($prevImages) ? ';' : '';


            $data['images'] = $prevImages. $dir .'/' .$file_name;
            $data['updated_at'] = date('y-m-d H:i:s');
            $sId = LeadRequest::where('id',$request->request_id)->update($data);
            return $this->sendResponse('Image Uploaded');
        }

        return $this->sendError('Something went wrong, try again!');
    }

    public function addDetailsToRequest(Request $request){
        $user_id = $request->user_id;

        $validator = Validator::make($request->all(), [
            'request_id' => 'required|integer|exists:lead_requests,id',
            'details' => 'required',
          ], [
            'image_file.required' => 'Location Postcode is required.'
        ]);
        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        $data['details'] = $request->details;
        $data['professional_letin'] = !empty($request->professional_letin)? $request->professional_letin : '0';
        $data['has_additional_details'] = '1';
        $sId = LeadRequest::where('id',$request->request_id)->update($data);
        if($sId){
            return $this->sendResponse('Details Added');
        }
         
        return $this->sendError('Something went wrong, try again!');
    }  
    

    public function checkParagraphQuality(Request $request){       

        $validator = Validator::make($request->all(), [
            'text' => 'required',
          ], [
            'text.required' => 'Text is required for checking the quality score.'
        ]);
        if($validator->fails()){
            return $this->sendError($validator->errors());
        }

        $text = $request->text;

        $response = Http::asForm()->post('https://api.languagetool.org/v2/check', [
            'text' => $text,
            'language' => 'en-US'
        ]);

        $data = $response->json();
        
        if(!empty($data)){

            $baseScore = 100;
            $errorCount = count($data['matches']);
            $wordCount = str_word_count($text);
            $errorPenalty = $errorCount * 5;
            $minParagraphWordLength = 20; 
            if ($wordCount < $minParagraphWordLength) {
                $lengthPenalty = ($minParagraphWordLength - $wordCount) * 5; 
            } else {
                $lengthPenalty = 0; 
            }
            $qualityScore = $baseScore - $errorPenalty - $lengthPenalty;
            $qualityScore = max(0, min(100, $qualityScore));

            // $rel['length_penalty'] = $lengthPenalty;
            // $rel['word_count'] = $wordCount;
            // $rel['error_count'] = $errorCount;
            $rel['text'] = $text;
            $rel['quality_score'] = $qualityScore;
            return $this->sendResponse('Quality Details',$rel);
        }
                 
        return $this->sendError('Something went wrong, try again!',$data);
    } 

}