<?php
/**
 * Utility functions
 *
 * @author Anshul <anshula@webaroo.com>
 */
class Utils {
    public static function httpRequest($url){        
        if(function_exists('curl_init')){
          $ch = curl_init();
          $timeout = 60;
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
