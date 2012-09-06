<?php
/**
 * Response being sent out. It can be used to send message independent of the
 * request
 *
 * @author Anshul <anshula@webaroo.com>
 */
require_once dirname(__FILE__).'/Message.php';
require_once dirname(__FILE__).'/Sender/Default.php';
class Response {
    public $messages = array();
    /**
     * Public variable so it can be set even through simple assignment
     * @var Sender
     */
    public $sender = null;

    public function  __construct(Sender $sender = NULL) {
        switch($sender){
            case NULL:
                $this->sender = new Sender_Default();
                break;
            default:
                $this->sender = $sender;
        }
    }
    /**
     * Sends the message using the selected Derived Sender Object
     */
    public function send(){
        foreach ($this->messages as $message) {
            /* @var $message Message */
            $this->sender->addMsg(
                $message->msisdn,
                $message->content,
                $message->sessionId,
                $message->time
            );
        }
        $this->sender->sendMsg();
    }
}
?>
