<?php
/**
 * A Request coming from SMS GupShup servers. This usually happens when a
 * mobile user sends a message for the registered App. Request object contains
 * mechanism to access different parameters from that request
 *
 * @author Anshul <anshula@webaroo.com>
 */
require_once dirname(__FILE__).'/Message.php';

class Request {
    /**
     *
     * @var Message
     */
    public $message;
    /**
     * Will have first word of the message
     * @var String
     */
    public $command;
    /**
     * Will have rest of the message
     * @var String
     */
    public $param;
    public function  __construct() {
        //@todo cleanup of the GET parameters
        if(isset($_GET['msisdn'])){
            if(!isset ($_GET['sessionId'])){
                $_GET['sessionId'] = NULL;
            }
            if(!isset ($_GET['content'])){
                $_GET['content'] = NULL;
            }
            $this->message = new Message(Message::INCOMING, $_GET['msisdn'], $_GET['content'], $_GET['sessionId']);
            if(!is_null($_GET['content'])){
                $this->parse();
            }
        }
    }
    /**
     * Parse the incoming request and seperate the command and params
     * to that command
     */
    public function parse(){
        $content = ltrim($this->message->content);
        $content = explode(' ', $content);
        $this->command = strtoupper(array_shift($content)); //@todo make it more unicode safe
        $this->param = implode(' ', $content);
    }
}
?>
