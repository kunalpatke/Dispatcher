<?php

/**
 * Description of RuleVendor
 * 
 * Consists of methods which deal with rule and vendor logic
 *
 * @author Abhijit
 */
class GenericClass {
        
    private $conn;
    private $ruleId;
    private $ruleType="DEFAULT";
    private $ruleData;
    private $timezone;
    private $table;
    private $transactionId;
    private $messageId;
    
    public function __construct($timezone) {
        $this->setTimezone($timezone);        
    }
    
    private function setTimezone($tz){
        $this->timezone = $tz;
        date_default_timezone_set($this->timezone);
    }
    
    public function getConnection($databaseType,$host,$db,$userName,$password,$table){
        $this->table = $table;
        require_once dirname(__FILE__).'/../../DBConnectors/'.$databaseType.'/DB.php';
        $this->conn = new DB($host,$db,$userName,$password);
    }
    
    public function getSchemaData($condition=""){
        $query = "select * from " . $this->table;
        if($condition !="")
            $query .= " " . $condition;
        $this->conn->runQuery($query);
        return $this->conn->getResult();
    }
    
    public function closeDBConnection(){
        $this->conn->closeCxn();
    }  
    
    public function getVendorData($ruleId){
        $this->ruleId = $ruleId;
        $this->conn->runQuery("select rp.vendor_load_balance as vlb, rp.vendor_regex as vr, rp.vendor_priority as vp, rp.vendor_failover_sequence as vfs from rules rs, rule_properties rp where rs.id = '" .$this->ruleId."' and rs.id = rp.rule_id");
        $vendorRules = $this->conn->getResult();
        if(sizeof($vendorRules) > 0){
            foreach($vendorRules as $vr){
                if($vr['vlb'] != ""){
                    $this->ruleType = "LOADBALANCE";
                    $this->ruleData = unserialize($vr['vlb']);
                }else{
                    if($vr['vr'] != ""){
                        $this->ruleType = "REGEX";
                        $this->ruleData = unserialize($vr['vr']);
                    }else{
                        if($vr['vp'] != ""){
                            $this->ruleType = "PRIORITY";
                            $this->ruleData = unserialize($vr['vp']);
                        }                        
                    }
                }
            }
            return $this->vendorInfo();
        }else{
            return FALSE;
        }
    }
    
    private function vendorInfo(){ 
        $vendor=array();
        if($this->ruleData){
            foreach($this->ruleData as $vid => $data){
                $vendor['data'] = $this->getVendor($vid);
            }
            return $vendor;
        }  else {
            return FALSE;
        }
    }
    
    public function getRuleType(){
        return $this->ruleType;
    }
    
    private function getVendor($vendorId){
        $this->conn->runQuery("select vendor.name as name,vp.protocol as protocol,vp.url as url,vp.params as params,vp.multiple_message_support as mutiSupport,vp.unicode_support as unicodeSupport from vendor, vendor_params vp where vendor.id = '".$vendorId."' and vendor.id = vp.vendor_id");
        return $this->conn->getResult();
    }
    
    public function getCurrentTimestamp(){
        return time()*1000;
    }
    
    public function getMessages($query){
        $this->conn->runQuery($query);
        return $this->conn->getResult();
    }
    
    public function getMessageId(){
        return str_replace(".","",microtime(true));
    }
    
    public function updateMessageTable($query){
        $this->conn->runQuery($query);
        return TRUE;
    }
    
    public function computeTransMsgId($format,$splitter,$transId,$msgId){
        if(strtoupper($format) == "DOUBLE"){
            $arr = explode($splitter,$transId);
            if(sizeof($arr) == 2){
                $this->transactionId = $arr[0];
                $this->messageId = $arr[1];
            }
            else{
                return FALSE;
            }
        }else{
            $this->transactionId = $transId;
            $this->messageId = $msgId;
        }
        return TRUE;
    }
    
    public function getTransId(){
        return $this->transactionId;
    }
    
    public function getMsgId(){
        return $this->messageId;
    }
    
    public function getColumnList($columns){
        $collist=false;
        if(is_array($columns)){
            foreach ($columns as $key=>$col) {
                $collist .= $col.",";
            }
            $collist = substr($collist, 0,-1);
        }
        return $collist;
    }
}
?>
