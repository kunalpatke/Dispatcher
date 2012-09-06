<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of callURL
 *
 * @author Anshula
 */
class callURL {
    public static function post($url, $params, $multipart = FALSE, $version= CURL_HTTP_VERSION_NONE){
        if(function_exists('curl_init')){
            $ch = curl_init();
            $timeout = 60;
            curl_setopt($ch,CURLOPT_URL,$url);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, $version);
            //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:')); Better use HTTP 1.0
            curl_setopt($ch, CURLOPT_POST, TRUE); //This line should appear before you set the params
            if($multipart){
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            }else{
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            }
            curl_setopt($ch,CURLOPT_TIMEOUT,$timeout);
            $data = curl_exec($ch);
            if($data === FALSE){
                throw new Exception(curl_errno($ch));
            }
            curl_close($ch);
            return $data;
        }else{
            return FALSE;
        }
    }
    public static function get($url, $params = NULL){
      
        if(function_exists('curl_init')){
          $ch = curl_init();
          $timeout = 60;
          if(is_array($params)){
              $url = $url."?".http_build_query($params);
          }
//          echo $url;
          curl_setopt($ch,CURLOPT_URL,$url);
          curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
          curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
          $data = curl_exec($ch);
          curl_close($ch);
        }else{
            $data = file_get_contents($url);
        }
      return $data;
    }
}
?>
