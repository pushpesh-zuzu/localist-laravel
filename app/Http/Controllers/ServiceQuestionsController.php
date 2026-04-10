<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceQuestion;
use App\Models\Category;
use App\Models\LeadRequest;
use App\Models\LeadPrefrence;
use App\Helpers\Zoho\ZohoQuestionAnswer;
use App\Helpers\CustomHelper;

use Illuminate\Support\Facades\Validator;

class ServiceQuestionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
         abort_if(!auth()->user()->can('servicequestions.viewlist'), 403, __('User does not have the right permissions.'));
         // Get all category IDs that have service questions
        // $categoryIdsWithQuestions = ServiceQuestion::distinct()->pluck('category')->toArray();

        // // Fetch only those categories which have questions
        // $aRows = Category::whereIn('id', $categoryIdsWithQuestions)
        //                 ->where('status', 1)
        //                 ->get();

        // // Attach service questions to each category
        // foreach ($aRows as $key => $value) {
        //     $value['servQuestions'] = ServiceQuestion::where('category', $value->id)->get();
        // }
        $aRows = ServiceQuestion::with('categories')->get();
        

        return view('servicequestion.index', get_defined_vars());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
          abort_if(!auth()->user()->can('servicequestions.create'), 403, __('User does not have the right permissions.'));
        $aRow = array();
        $categories = Category::where('status',1)->get();
        return view('servicequestion.create',get_defined_vars());
    }

    // Show the form for editing the specified category
    public function edit(Request $request, $id)
    {
        abort_if(!auth()->user()->can('servicequestions.edit'), 403, __('User does not have the right permissions.'));
        $aRow = ServiceQuestion::where('id',$id)->first();
        $categories = Category::where('status',1)->get();
        // echo "<pre>";
        // print_r($aRow);
        // $answerArray = json_decode($aRow->answer, true);
        // print_r($answerArray);
        // exit;
        return view('servicequestion.create',get_defined_vars());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        abort_if(!auth()->user()->can('servicequestions.create'), 403, __('User does not have the right permissions.'));
        echo "<pre>";print_r($request->all());
        $validator = Validator::make($request->all(), [
            'category' => 'required|integer|exists:categories,id',
            'question_no' => 'required',
            'questions' => 'required',
            'ques_opt' => 'required',
            'next_ques' => 'required',
            'ques_score' => 'required',
            'option_type' => 'required',
            'question_type' => 'required'
            ], [
            'service_id.exists' => 'Provided service id does not exists.',
            'option_type.required' => 'Option selection type is required.'
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }
        $serviceId = $request->category;
        $data['category'] = $serviceId;
        $data['question_no'] = $request->question_no;
        $data['questions'] = $request->questions;
        $answer = [];
        $leadPrefAns = "";
        $ques_opt = $request->ques_opt;
        $ques_score = $request->ques_score;
        $next_ques = $request->next_ques;
        foreach($ques_opt as $i => $opt){
            $temp['option'] = $opt;
            $temp['score'] = $ques_score[$i];
            $temp['next_question'] = $next_ques[$i];
            array_push($answer, $temp);
            
            if(empty($leadPrefAns)){
                $leadPrefAns .= $opt;
            }else{
                $leadPrefAns .= "," .$opt;
            }
        }

        
        $data['answer'] = json_encode($answer);
        $data['option_type'] = $request->option_type;
        $data['question_type'] = $request->question_type;
        print_r($data);
        // exit;
        $id = ServiceQuestion::insertGetId($data);

        $user_ids = LeadPrefrence::query()
            ->where('service_id', $serviceId)
            ->distinct()
            ->pluck('user_id');
        foreach($user_ids as $uid){
            $d2['user_id'] = $uid;
            $d2['service_id'] = $serviceId;
            $d2['question_id'] = $id;
            $d2['answers'] = $leadPrefAns;
            $d2['created_at'] = date('Y-m-d H:i:s');
            LeadPrefrence::insertGetId($d2);
            // CustomHelper::runInBackground(function () use ($uid, $serviceId) {
            //     app(ZohoQuestionAnswer::class)->integrateServiceQaSingle($uid, $serviceId);
            // });
        }
        
        return redirect()->route('servicequestion.index')->with('success', 'Questions created successfully.');
    }

    public function update(Request $request, $id)
    {
        abort_if(!auth()->user()->can('servicequestions.edit'), 403, __('User does not have the right permissions.'));

        echo "<pre>";print_r($request->all());
        $validator = Validator::make($request->all(), [
            'category' => 'required|integer|exists:categories,id',
            'question_no' => 'required',
            'questions' => 'required',
            'ques_opt' => 'required',
            'next_ques' => 'required',
            'ques_score' => 'required',
            'option_type' => 'required',
            'question_type' => 'required'
            ], [
            'service_id.exists' => 'Provided service id does not exists.',
            'option_type.required' => 'Option selection type is required.'
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors());
        }
        $oldQuestionAndOptions =  ServiceQuestion::where('id', $id)->first();
        $serviceId = $request->category;

        $data['category'] = $serviceId;
        $data['question_no'] = $request->question_no;
        $data['questions'] = $request->questions;
        $answer = [];
        $ques_opt = $request->ques_opt;
        $ques_score = $request->ques_score;
        $next_ques = $request->next_ques;
        foreach($ques_opt as $i => $opt){
            $temp['option'] = $opt;
            $temp['score'] = $ques_score[$i];
            $temp['next_question'] = $next_ques[$i];
            array_push($answer, $temp);
        }

        
        $data['answer'] = json_encode($answer);
        $data['option_type'] = $request->option_type;
        $data['question_type'] = $request->question_type;
        $uid = ServiceQuestion::where('id',$id)->update($data);

        $leadPrefAns = LeadPrefrence::where('service_id', $serviceId)
            ->where('question_id', $id)->get();

        $oldOptions = $request->old_ques_opt;
        $newOptions = $request->ques_opt;

        // Step 1: Build mapping (old → new)
        
        // Build option map (old → new)
        $optionMap = [];
        foreach ($oldOptions as $i => $oldOpt) {
            if (isset($newOptions[$i])) {
                $optionMap[$oldOpt] = $newOptions[$i];
            }
        }
        
        // Step 2: Find newly added options
        $newlyAddedOptions = array_diff($newOptions, $oldOptions);


        //for lead preference table
        foreach ($leadPrefAns as $lead) {

            $existingAnswers = array_filter(array_map('trim', explode(',', $lead->answers)));

            $updatedAnswers = [];

            foreach ($existingAnswers as $ans) {

                // Case 1: renamed → replace
                if (isset($optionMap[$ans])) {
                    $updatedAnswers[] = $optionMap[$ans];
                    continue;
                }

                // Case 2: keep only if still exists in new options
                if (in_array($ans, $newOptions)) {
                    $updatedAnswers[] = $ans;
                }

                // else → deleted → drop
            }

            // Case 3: add all new options (force pre-select)
            $finalAnswers = array_unique(array_merge($updatedAnswers, $newlyAddedOptions));

            $lead->answers = implode(',', $finalAnswers);
            $lead->save();

        }


        $oldQuestion = $oldQuestionAndOptions->questions;
        $newQuestion = $request->questions;
        //for lead_requests table
        $leadRequests = LeadRequest::where('service_id', $serviceId)->get();        

        foreach ($leadRequests as $lead) {
        
            // print_r("old questions: " .$lead->questions ."\n");
            // print_r("old arrayed_questions: " .$lead->arrayed_questions ."\n");
            // =========================
            // 1. QUESTIONS COLUMN
            // =========================
            $questions = json_decode($lead->questions, true);

            foreach ($questions as &$q) {

                // Replace question text
                if (isset($q['ques']) && $q['ques'] === $oldQuestion) {
                    $q['ques'] = $newQuestion;
                }

                // Replace answers (comma string)
                if (isset($q['ans'])) {

                    $answers = array_map('trim', explode(',', $q['ans']));
                    $updated = [];

                    foreach ($answers as $ans) {
                        $updated[] = $optionMap[$ans] ?? $ans;
                    }

                    $q['ans'] = implode(', ', array_unique($updated));
                }
            }

            $lead->questions = json_encode($questions);



            // =========================
            // 2. ARRAYED QUESTIONS COLUMN
            // =========================
            $arrayed = json_decode($lead->arrayed_questions, true);

            foreach ($arrayed as &$q) {

                // Replace question text
                if (isset($q['ques']) && $q['ques'] === $oldQuestion) {
                    $q['ques'] = $newQuestion;
                }

                // Replace answers (array)
                if (isset($q['ans']) && is_array($q['ans'])) {

                    $updated = [];

                    foreach ($q['ans'] as $ans) {
                        $updated[] = $optionMap[$ans] ?? $ans;
                    }

                    $q['ans'] = array_values(array_unique($updated));
                }
            }

            $lead->arrayed_questions = json_encode($arrayed);

            $lead->save();
            // print_r("questions: " .$lead->questions ."\n");
            // print_r("arrayed_questions: " .$lead->arrayed_questions ."\n#############################\n\n");
        }
        

        return redirect()->route('servicequestion.index')->with('success', 'Questions updated successfully.');
    }


    // Remove the specified question from service
    public function destroy(Request $request, $id)
    {
        abort_if(!auth()->user()->can('servicequestions.delete'), 403, __('User does not have the right permissions.'));
        $serviceQuestion = ServiceQuestion::where('id', $id)->first();
        $serviceId = $serviceQuestion->category;
        $questionNumber = $serviceQuestion->question_no;

        LeadPrefrence::where('service_id', $serviceId)->where('question_id', $questionNumber)->delete();
        ServiceQuestion::where('id', $id)->delete();

        return redirect()->route('servicequestion.index')->with('success', 'Question deleted successfully.');
    }



    public function addMoreOption(Request $request){
        return response()->json([
            'status'    => true,
            'html'      => view("servicequestion.add-more-option",['type'=>$request->type,'count'=>$request->count])->render()
        ]);
    }

    
}
