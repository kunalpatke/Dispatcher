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
    
    public function __construct() {}
    
    public function getConnection($databaseType,$host,$hostName,$db,$userName,$password){
        require_once dirname(__FILE__).'/../../DBConnectors/'.$databaseType.'/DB.php';
        $this->conn = new DB($host,$hostName,$db,$userName,$password);
    }
    
    public function getSchemaData(){
        $this->conn->runQuery("select * from schemainfo");
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
}

?>
