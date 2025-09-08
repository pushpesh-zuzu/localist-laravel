<?php

namespace App\Helpers;

class CreditScorePredictor{

    public static function predict($servie_id, $predict, $data){
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
                $tRel = number_format(($output['prediction'] * 1.17), 5);
                $rel = ceil($tRel);
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

}
