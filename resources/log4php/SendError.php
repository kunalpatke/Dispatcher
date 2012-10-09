<?php

/**
 * Description of SendError
 *
 * @author Abhijit
 */

class SendError {

    private static $entId;
    private static $entPwd;   
    private static $entUrl;
    private static $isSMS;
    private static $isMail;
    private static $recipients ;   
    private static $emailRecipients ;
    private static $subject = "ERROR !!!! Dispatcher";
    private static $header = "";
    

    public static function sendMsg($message){
        self::setConfigData();
        if(self::$isSMS){
            require_once dirname(__FILE__).'/../GupShup/Sender/Enterprise.php';            
            $enterprise = new Sender_Enterprise(self::$entId,self::$entPwd,self::$entUrl);
            foreach(self::$recipients as $msisdn) {
                $enterprise->addMsg($msisdn, $message);
            }
            $response = $enterprise->sendMsg();
        }
        if(self::$isMail){
            self::sendEmail($message);
        }
        return TRUE;
    }
    
    private static function setConfigData(){
        $configData = parse_ini_file("/../../config/setup.properties",true);
        $senderInfo = $configData['sender'];     
        self::$entId = $senderInfo['id'];
        self::$entPwd = $senderInfo['password'];
        self::$entUrl = $senderInfo['url'];
        $exceptionData = $configData['exception'];
        self::$isSMS = $exceptionData['issms'];
        self::$isMail = $exceptionData['isemail'];        
        self::$recipients = explode(',', $exceptionData['smsNumbers']);
        self::$emailRecipients = explode(',', $exceptionData['emailIds']);
        self::$subject = $exceptionData['emailSubject'];
    }
    public static function sendEmail($message){ 
        require_once dirname(__FILE__).'/../EmailSender.class.php';        
        $mail=array();
        foreach(self::$emailRecipients as $mailid){
            $mail[$mailid]="";
        }
        $head = array(
                'to'   =>$mail,
                'from'    =>array("noreply@webaroo.com"=>"Gupshup"));        
        if(EmailSender::send($head,  self::$subject,$message)){
            return TRUE;         
        }
    }  
}
?>