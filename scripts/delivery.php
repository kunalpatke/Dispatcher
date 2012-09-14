<?php

/**
 * Description of delivery
 * 
 * This script gets invoked when operator responds back with the delivery status for each message.
 * Paramters to be read(TransactionId/Status/Cause/Timestamp/MessageID) are definded in  config/delivery.properties file
 *
 *
 * @author Abhijit <abhijit@webaroo.com>
 */
require_once dirname(__FILE__).'/class/GenericClass.php';
require_once dirname(__FILE__).'/../resources/log4php/Logger.php';
Logger::configure('../config/log.properties');
$logger = Logger::getLogger("Dispatcher.delivery");
$logger->info("Recieved hit with params: " . $_SERVER['QUERY_STRING']);

$configData = parse_ini_file("../config/setup.properties",true);
$deliveryData = $configData['delivery'];
if(is_array($deliveryData)){
    $logger->info("Delivery setup info found");
    $phone = $_REQUEST["'".$deliveryData['phone']."'"];
    $timestamp = $_REQUEST["'".$deliveryData['timestamp']."'"];
    $status = strtoupper($_REQUEST["'".$deliveryData['status']."'"]);
    $cause = $_REQUEST["'".$deliveryData['cause']."'"];
    $accountType = $_REQUEST["'".$deliveryData['account_type']."'"];
    
    $obj = new GenericClass($deliveryData['timezone']);
    
    if($obj->computeTransMsgId($deliveryData['transMsgFormat'],$deliveryData['stringSplitter'],$_REQUEST["'".$deliveryData['transactionId']."'"],$_REQUEST["'".$deliveryData['messageId']."'"])){
        $transId = $obj->getTransId();
        $msgId = $obj->getMsgId();
        $logger->info("TransactionId: ". $transId . ", MsgId: " . $msgId);
        $customDB = $configData['custom_db'];
        $obj->getConnection(strtoupper($customDB['database_type']),$customDB['host'],$customDB['database'],$customDB['username'],$customDB['password'],$customDB['table']);        
        $schemaData = $obj->getSchemaData(" where account_type = '$accountType'");  
        $obj->closeDBConnection();
        $logger->info("Host info for this message located");
        if(sizeof($schemaData) != 0){
            foreach($schemaData as $hostData){
                if($hostData['status']){
                    $logger->info("Connecting to ".$hostData['host']);
                    $obj->getConnection($hostData['database_type'],$hostData['host'],$hostData['database_name'],$hostData['username'],$hostData['password'],$hostData['table_name']);
                    $obj->updateMessageTable("UPDATE $table set STATUS='$status' , DELIVERYTIME =  '$timestamp' , CAUSE = '$cause' where TRANSACTIONID = '$transId'");
                    $obj->closeDBConnection(); 
                    $logger->info("Status Updated.. " . "Phone: $phone | Delivery Time: $timestamp | Status: $status | Cause: $cause | causeId: $transId | messageId: $msgId");
                    if($status != $deliveryData['successStatusText']){
                        // retry if not delivered
                        $logger->info("Message not delivered. retrying");
                    }
                }
            }            
        }else{
            $logger->warn("No host found for $accountType");
        }
    }else{
        $logger->error("Error in computing transaction id. Format: " . $deliveryData['transMsgFormat'] . ", Splitter: " .$deliveryData['stringSplitter'] . ", TransactionId: " . $_REQUEST["'".$deliveryData['transactionId']."'"] . ", MsdId: " . $_REQUEST["'".$deliveryData['messageId']."'"]);
    }
}
?>
