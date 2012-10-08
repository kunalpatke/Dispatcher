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
require_once dirname(__FILE__).'/class/GenericClass.php';
//Fetch Host parameters
$host = $_REQUEST['host'];
$databaseType=strtoupper($_REQUEST['database_type']);
$db=$_REQUEST['db'];
$username=$_REQUEST['username'];
$password = $_REQUEST['password'];
$priority = $_REQUEST['priority'];
$table=$_REQUEST['table'];
$query=$_REQUEST['query'];
$ruleType = $_REQUEST['ruleType'];
$vendorData = $_REQUEST['vendorData'];
$timezone = $_REQUEST['timezone'];
$transactionMode = $_REQUEST['accountType'];
$columns = unserialize($_REQUEST['columns']);

$obj = new GenericClass($timezone);        
Logger::configure('../config/log.properties');
$logger = Logger::getLogger("Dispatcher.getMsg");
$logger->info("Recieved hit for host: " . $_REQUEST['host']);
    
try{
    switch(strtoupper($priority)){
        case 'LOW':
            sleep(5);
            break;
        case 'MEDIUM':
            sleep(2);
            break;
    }
    $obj->getConnection($databaseType,$host,$db,$username,$password,$table);
    $pickedTime = $obj->getCurrentTimestamp();  
    $data = $obj->getMessages($query);
    if(sizeof($data)>0){
        $logger->info("Reading each message");
        if($vendorData == "" || $ruleType=="DEFAULT"){
            // send using gupshupAPI
            require_once dirname(__FILE__).'/../resources/GupShup/Sender/Enterprise.php';
            $configData = parse_ini_file("../config/setup.properties",true);
            $senderInfo = $configData['sender'];
            $logger->info("Using Gupshup credentials for message sending: ". $senderInfo['id']);
            $sender = new Sender_Enterprise($senderInfo['id'], $senderInfo['password'],$senderInfo['url']);                       
        }
        $msgIdQuery = "UPDATE $table set " . $columns['messageid'] . " = CASE ";
        foreach($data as $val){           
            $msgId .= $val[$columns['id']] . ",";    
            $messageID = $obj->getMessageId();
            $sender->addMsg($val[$columns['phone']],$val[$columns['message']],$messageID);  
            $msgIdQuery .= " when " . $columns['id'] . " = " . $val[$columns['id']] . " Then '$messageID'";
        }
        $msgId = substr($msgId, 0,-1);
        $msgIdQuery .= " END where " . $columns['id'] . " in ($msgId)";        
        $obj->updateMessageTable($msgIdQuery);          
        $obj->updateMessageTable("UPDATE $table set " . $columns['status'] . "='PICKED' , " . $columns['submittime'] . " = '$pickedTime' , " . $columns['mode'] . " = '$transactionMode' where " . $columns['id'] . " in ($msgId)");        
        $logger->info("Message status updated to PICKED on $host");        
        // check for ruleType and decide the msg distribution accross vendors          
        try{
            $response = $sender->sendMsg();		
        }catch(Exception $e){
            $logger->error("Exception occured in message sending. " . $e->getMessage());
            $response->error = "Message sending exception: " . $e->getMessage();
        }
        $processedTime = $obj->getCurrentTimestamp();  
        if($response->success){
            $logger->info("Message succesfully sent. API Response: " . $response->response); 
            $status = "INPROCESS";
        }else{
            $logger->info("Message sending error. API Response: " . $respone->error); 
            $status = "FAILED";
        }
        $obj->updateMessageTable("update $table set " . $columns['attempt'] . " = '1', " . $columns['status'] . "='$status' , " . $columns['transactionid'] . "  = '$response->transactionId' , " . $columns['processtime'] . " = '$processedTime' where " . $columns['id'] . " in ($msgId)");
        $logger->info("Message status updated to $status on $host");            
    }else{
        $logger->warn("No messages found on $host ");
    }
    $obj->closeDBConnection();
    exit();
    // get vendor and vendor rules
    // send messages
}
catch(Exception $e){        
    $logger->error("Exception: " . $e->getMessage());
}
?>
