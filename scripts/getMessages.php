<?php

/**
 * Description of Connector.
 * 
 * Recieves info from run.php asynchronously about the hosts. makes DB connections and processes 
 * each request as a seperate apache process
 *
 * @author Abhijit <abhijit@webaroo.com>
 */
set_time_limit(0);
ini_set('memory_limit','1G');
require_once dirname(__FILE__).'/../resources/log4php/Logger.php';
Logger::configure('../config/log.properties');
$logger = Logger::getLogger("Dispatcher.getMsg");
$logger->info("Recieved hit for host: " . $_REQUEST['host']);
//Fetch Host parameters
$host = $_REQUEST['host'];
//$hostName=$_REQUEST['host_name'];
$databaseType=strtoupper($_REQUEST['database_type']);
$database=$_REQUEST['db'];
$username=$_REQUEST['username'];
$password = $_REQUEST['password'];
$priority = $_REQUEST['priority'];
$table=$_REQUEST['table'];
$query=$_REQUEST['query'];
$ruleType = $_REQUEST['ruleType'];
$vendorData = $_REQUEST['vendorData'];
require_once dirname(__FILE__).'/../DBConnectors/'.$databaseType.'/DB.php';
// include DB connector and create a DB connection to the given host
//require_once dirname(__FILE__).'/../class/RuleVendor.php';
try{
    switch(strtoupper($priority)){
        case 'LOW':
            sleep(5);
            break;
        case 'MEDIUM':
            sleep(2);
            break;
    }
    
    $conn = new DB($host,$hostName,$database,$username,$password);    
    $conn->runQuery($query);
    $pickedTime = time();
    $data = $conn->getResult();
    if(sizeof($data)>0){
        $logger->info("Reading each message");
        if($vendorData == "" || $ruleType=="DEFAULT"){
            // send using gupshupAPI
            require_once dirname(__FILE__).'/../resources/GupShup/Sender/Enterprise.php';
            $sender = new Sender_Enterprise('2000022337', 'ketan123', '');
        }
        foreach($data as $val){           
            $msgId .= $val['MESSAGEID'] . ",";    
            $sender->addMsg($val['PHONENUMBER'],$val['MESSAGETEXT']);
        }        
        $msgId = substr($msgId, 0,-1);        
        $conn->runQuery("UPDATE $table set SUCCESSSTATUS='PICKED' , SUBMITTEDTIME = '$pickedTime' where MESSAGEID in ($msgId)");
        $logger->info("Message status updated to PICKED on $host");        
        // check for ruleType and decide the msg distribution accross vendors          
        $response = $sender->sendMsg();
		
        $processedTime = time();
        if($response->success){
            $logger->info("Message succesfully sent. API Response: " . $response->response); 
            $status = "INPROCESS";
        }else{
            $logger->info("Message seding error. API Response: " . $respone->error); 
            $status = "FAILED";
        }
        $conn->runQuery("update $table set ATTEMPTCOUNT = '1', SUCCESSSTATUS='$status' , MIPRESPONSEID = '$response->transactionId' , PROCESSEDTIME = '$processedTime' where MESSAGEID in ($msgId)");
        $logger->info("Message status updated to INPROCESS on $host");            
    }else{
        $logger->warn("No messages found on $host ");
    }
    $conn->closeCxn();
    exit();
    // get vendor and vendor rules
    // send messages
}
catch(Exception $e){        
    $logger->error("Exception: " . $e->getMessage());
}
?>
