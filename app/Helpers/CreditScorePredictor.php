<?php

namespace App\Helpers;

class CreditScorePredictor{

    public static function predict($servie_id, $data){
        $rel = 0;
        $url = "";
        switch($servie_id){
            case 27:
                $url = 'https://localist.pythonanywhere.com/predict/logo_design';
                break;
            case 33:
                $url = 'https://localist.pythonanywhere.com/predict/landscaping';
                break;
            case 34:
                $url = 'https://localist.pythonanywhere.com/predict/garage_conversion';
                break;
            case 35:
                $url = 'https://localist.pythonanywhere.com/predict/business_consulting';
                break;
            default:
                $url = "";
        }
        $output = self::getPrediction($url, $data);
        if(!empty($output['success'])){
            if($output['success'] == 1){
                $tRel = number_format($output['prediction'], 5);
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