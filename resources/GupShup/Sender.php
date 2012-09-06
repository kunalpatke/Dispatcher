<?php
/**
 * Sender Class, will be mainly extended to form other senders
 *
 * @author Anshul <anshula@webaroo.com>
 */
require_once dirname(__FILE__).'/Message.php';
class Sender {
    public $messages = array();
    /**
     *
     * @param String $msisdn
     * @param String $content
     * @param Int $sessionId
     * @param DateTime $time
     */
    public function addMsg($msisdn, $content, $sessionId = NULL, $time = NULL) {
        $this->messages[] = new Message(Message::OUTGOING, $msisdn, $content, $sessionId, $time);
    }
}
?>
