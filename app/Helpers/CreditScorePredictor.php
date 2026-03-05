<?php

namespace App\Helpers;
use App\Models\Category;
use App\Models\ServiceQuestion;

class CreditScorePredictor{

    public static function getCreditScoreFromLaravel($service_id, $data){
        $sQuestions = ServiceQuestion::where('category',$service_id)->get()->toArray();
        $requestQuestions = json_decode($data, true);

        $serviceQuestions = [];
        $leadQuestions = [];
        foreach($sQuestions as $sq){
            $temp['ques'] = $sq['questions'];
            $ans = [];
            $ansDecoded = json_decode($sq['answer'], true);
            foreach($ansDecoded as $a){
                $temp2['option'] = $a['option'];
                $temp2['score'] = $a['score'];
                $ans[] = $temp2;
            }
            $temp['ans'] = $ans;
            $serviceQuestions[] = $temp;
        }

        foreach($requestQuestions as $rq){
            $temp['ques'] = $rq['ques'];
            $temp['ans'] = $rq['ans'];
            $leadQuestions[] = $temp;
        }

        $scoreArray = self::createScoreArray($serviceQuestions, $leadQuestions);

        $creditScore = 0;
        foreach($scoreArray as $sa){
            // Calculate total score logic can be implemented here
            $creditScore += $sa['score'] ?? 0;
        }

        // on 5/03/2026
        if($service_id == 112){
            // tree surgery by 30%
            $tRel = number_format(($creditScore * 1.30), 5);
            $creditScore = ceil($tRel);
        }

        if($service_id == 113){
            // roofing by 20%
            $tRel = number_format(($creditScore * 1.20), 5);
            $creditScore = ceil($tRel);
        }
        
        return $creditScore;        
    }

    public static function getCreditScoreFromPython($servie_id, $predict, $data){
        $rel = 0;
        $url = "https://localists.com/credit_score_prediction/predict/";
        switch($servie_id){
            case 43:
                $url .= 'landscaping';
                break;
            case 49:
                $url .= 'fence_and_gate';
                break;
            case 51:
                $url .= 'driveway_installation';
                break;
            case 52:
                $url .= 'patio_services';
                break;
            case 54:
                $url .= 'artificial_grass';
                break;
            

            default:
                $url = "";
        }
        $data = json_decode($data, true);
        foreach ($data as $q) {
            if (is_array($q) && isset($q['ques'])) {
                $predict[$q['ques']] = !empty($q['ans']) ? preg_replace(['/^,/', '/\?$/'], '', trim($q['ans'])) : 'Unknown';
            }
        }

        $output = self::getPrediction($url, $predict);
        if(!empty($output['success'])){
            if($output['success'] == 1){
                // increase by 17%
                $tRel = number_format(($output['prediction'] * 1.17), 5);
                $rel = ceil($tRel);

                if($servie_id == 49){
                    // increase by 37%
                    $tRel = number_format(($rel * 1.37), 5);
                }else{
                    // increase by 27%
                    $tRel = number_format(($rel * 1.27), 5);
                }
                $rel = ceil($tRel);

                if($servie_id == 43 || $servie_id == 49 || $servie_id == 51){
                    // increase by 30%
                    $tRel = number_format(($rel * 1.30), 5);
                    $rel = ceil($tRel);
                }

                // #on 5/3/2026
                if($servie_id == 43 || $servie_id == 51){
                    // landscaping and driveway increase by 20%
                    $tRel = number_format(($rel * 1.20), 5);
                    $rel = ceil($tRel);
                }

                if($servie_id == 49){
                    // fence and gate increase by 30%
                    $tRel = number_format(($rel * 1.30), 5);
                    $rel = ceil($tRel);
                }

                if($servie_id == 52){
                    // patio increase by 40%
                    $tRel = number_format(($rel * 1.40), 5);
                    $rel = ceil($tRel);
                }
                


            }else{
                print_r($output);
            }
        }else{
            print_r($output);
        }
        return $rel;
    }

    private static function getPrediction($url, $data){
        $jsonData = json_encode($data);
        // Initialize cURL session
        $ch = curl_init($url);
        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        // Execute cURL request
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }


    private static function normalizeQuestion($ques) {
        $ques = strtolower($ques);
        $ques = preg_replace('/[^a-z0-9 ]/', '', $ques);
        $ques = trim($ques);
        return $ques;
    }

    private static function createScoreArray($serviceQuestions, $leadQuestions) {
        $scoreArray = [];
        foreach ($leadQuestions as $lead) {
            $question = $lead['ques'];
            $answer = $lead['ans'];

            $score = self::getScoreForAnswer($question, $answer, $serviceQuestions);

            // If answer is array, join with comma for output format
            $answerStr = is_array($answer) ? implode(', ', $answer) : $answer;

            $scoreArray[] = [
                'ques' => $question,
                'ans' => $answerStr,
                'score' => $score,
            ];
        }
        return $scoreArray;
    }

    private static function getScoreForAnswer($question, $ans, $serviceQuestions) {
        $normQues = self::normalizeQuestion($question);

        // Find matching question in serviceQuestions
        foreach ($serviceQuestions as $q) {
            if (self::normalizeQuestion($q['ques']) === $normQues) {
                $options = $q['ans'];

                // Handle multiple answers as array or comma-separated string
                if (!is_array($ans)) {
                    $ansList = array_map('trim', explode(',', $ans));
                } else {
                    $ansList = $ans;
                }

                $maxScore = null;
                foreach ($ansList as $answer) {
                    $foundScore = null;
                    foreach ($options as $opt) {
                        if (self::normalizeQuestion($opt['option']) === self::normalizeQuestion($answer)) {
                            if ($foundScore === null || $opt['score'] > $foundScore) {
                                $foundScore = $opt['score'];
                            }
                        }
                    }
                    if ($foundScore === null) {
                        // Not found, assign "Something else (please describe)" score
                        foreach ($options as $opt) {
                            if (stripos($opt['option'], 'something else') !== false) {
                                $foundScore = $opt['score'];
                                break;
                            }
                        }
                    }
                    if ($maxScore === null || $foundScore > $maxScore) {
                        $maxScore = $foundScore;
                    }
                }
                return $maxScore;
            }
        }
        return null;
    }


}
