<?php
/**
 * Description of Alert
 *
 * @author Anshul <anshula@webaroo.com>
 */
require_once dirname(__FILE__).'/GupShup/Sender/Enterprise.php';
class Alert {
    private static $_id = '2000022337';
    private static $_password = '6Epfpn';
    private static $_mask = NULL;

    public static function send($msisdn, $content){
        $sender = new Sender_Enterprise(self::$_id, self::$_password, self::$_mask);
        $sender->addMsg($msisdn, $content);
        $sender->sendMsg();
    }
}
?>
