<?php
/**
 *
 * @author Anshul <anshula@webaroo.com>
 */
class Message {
    const OUTGOING = 'OUTGOING';
    const INCOMING = 'INCOMING';
    public $msisdn;
    public $content;
    public $sessionId;
    public $type;
    public $time;

    public function  __construct($type, $msisdn, $content, $sessionId = NULL, $time = NULL) {
        $this->type = $type;
        $this->msisdn = $msisdn;
        $this->content = $content;
        $this->sessionId = $sessionId;
        $this->time = $time;
    }
}
?>
