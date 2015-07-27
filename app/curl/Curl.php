<?php namespace App\curl;

class Curl {
       public static function postCurl($uri,$array=false,$file=false)
       {
              $ch = curl_init();
              $url= $uri;
              $email = '';
              $token = '';
              curl_setopt($ch, CURLOPT_URL,$url);
              curl_setopt($ch, CURLOPT_HEADER, false);
              curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
              curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
              curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
              curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
              curl_setopt($ch, CURLOPT_POST,1);
              if($file){
              	$array['document_file'] =  new CurlFile($file['path'],$file['file_type'],$file['document_file']);
              }
              curl_setopt($ch, CURLOPT_POSTFIELDS,$array);
              curl_setopt($ch, CURLOPT_USERPWD, $email.":".$token);
              $returned = curl_exec($ch);
              curl_close($ch);
              $returned = json_decode($returned,1);
              return $returned;
       }

       public static function getCurl($uri,$array=null)
       {
               $url= $uri;
               $email = '';
               $token = '';
               $ch = curl_init();
               curl_setopt($ch, CURLOPT_URL,$url);
               curl_setopt($ch, CURLOPT_HEADER, false);
               curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
               curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
               curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
               curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
               curl_setopt($ch, CURLOPT_USERPWD, $email.":".$token);
               $returned = curl_exec($ch);
               curl_close($ch);
               $returned = json_decode($returned,true);
               return $returned;
       }
}