<?php
/**
 * Use a Gupshup Enterprise account to send messages.
 * IMPORTANT: Concept of Session IDs doesn't hold for Enterprise accounts
 *
 * @author Anshul <anshula@webaroo.com>
 */
require_once dirname(__FILE__).'/../Sender.php';
require_once dirname(__FILE__).'/../Utils.php';
require_once dirname(__FILE__).'/../../callURL.php';

class Sender_Enterprise extends Sender {
    public $id;
    public $password;
    public $msgType = '';
    /**
     * Mak that would appear on reciever's phone. For what can appear here,
     * contact SMS GupShup Support
     * @var String
     */
    public $mask;
    private $_url = "http://enterprise.smsgupshup.com/GatewayAPI/rest";
   // private $_url = "http://test.smsgupshup.com/GatewayAPI/rest";
    //private $_url = "http://localhost/fileHandler.php";

    public function  __construct($id, $password, $mask = NULL,$msgType=NULL) {
        $this->id       = $id;
        $this->password = $password;
        $this->mask     = $mask;
        if($msgType != "")
            $this->msgType = $msgType;
    }
    /**
     * Sends the response
     * @return Boolean
     */
    public function sendMsg() {
        $rows = array();
        $currentTime = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        $msgCount = sizeof($this->messages);
        if($msgCount == 0) {
            return TRUE; //Nothing to do for us
        }else if($msgCount == 1) {
            $message = $this->messages[0];
            if(is_null($message->time)) {
                $message->time = $currentTime;
            }
            $params = array();
            $params['userid']   = $this->id;
            $params['password'] = $this->password;
            $params['send_to']  = $message->msisdn;
            $params['msg']      = $message->content;
            $params['timestamp'] = $message->time->format('Y-m-d H:i:s');
            if(!is_null($this->mask)) {
                $params['mask'] = $this->mask;
            }
            $params['method']   = 'sendMessage';
            $params['v']        = '1.1';
            if($this->msgType != '')
                $params['msg_type']   = $this->msgType;
            $response = Utils::httpRequest($this->_url.'?'.http_build_query($params));
        }else {
            foreach ($this->messages as $message) {
                /* @var $message Message */
                if(is_null($message->time)) {
                    $message->time = $currentTime;
                }
                $rows[] = array(
                        $message->msisdn,
                        $message->content,
                        $this->mask,
                        $message->time->format('Y-m-d H:i:s')
                );
            }
            $fileName = tempnam(sys_get_temp_dir(), 'EnterpriseUpload').'.csv';
            $myFile = fopen($fileName, 'w');
            fputs($myFile,
                    '"'
                    .implode('","', array(
                    'PHONE',
                    'MESSAGE',
                    'MASKS',
                    'TIMESTAMPS'
                    ))
                    .'"'
                    ."\n"
            );
            foreach ($rows as $row) {
                fputcsv($myFile, $row, ',', '"');
            }
            fclose($myFile);
            $params = array();
            $params['method'] = 'xlsUpload';
            $params['userid'] = $this->id;
            $params['password'] = rawurlencode($this->password);
            $params['filetype'] = 'csv';
            $params['auth_scheme'] = 'PLAIN';
            $params['v'] = '1.1';
            $params['xlsFile'] = '@'.realpath($fileName);
            if($this->msgType != '')
                $params['msg_type']   = $this->msgType;
            $response = callURL::post($this->_url, $params, TRUE, CURL_HTTP_VERSION_1_0);
            unlink($fileName);
        }
        $return = new stdClass();
        $return->success = preg_match('/^success/', $response);        
		if($return->success) {
            $data = explode("|",$response);            
            preg_match("/\d+/", $data[sizeof($data)-1], $matches);
            $causeId = $matches[0];
            $return->transactionId = $causeId;
            $return->response = $response;
        }
        else{
            $return->error = $response;
        }
        return $return;
    }
}

//$sender = new Sender_Enterprise('2000022337', 'ketan123', '');
//$sender->addMsg('919833598918', "This is 10 Minutes");
////$sender->addMsg('919833598918', "This is 10 Minutes");
//$response = $sender->sendMsg(); 
////print_r($response);
//echo $response->transactionId;
?>
