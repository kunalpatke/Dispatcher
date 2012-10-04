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
$timezone= $configData['timezone']['timezone'];  
if(is_array($deliveryData)){    
    $logger->info("Delivery setup info found");
    $phone = $_REQUEST[$deliveryData['phone']];
    $timestamp = $_REQUEST[$deliveryData['timestamp']];
    $status = strtoupper($_REQUEST[$deliveryData['status']]);
    $cause = $_REQUEST[$deliveryData['cause']];
    $accountType = $_REQUEST[$deliveryData['accountType']];
    $logger->info("Phone: $phone , Status: $status, Cause: $cause, Time: $timestamp");
    $attemptCount = $deliveryData['attemptCount'];
    $obj = new GenericClass($timezone);
    
    if($obj->computeTransMsgId($deliveryData['transMsgFormat'],$deliveryData['stringSplitter'],$_REQUEST[$deliveryData['transactionId']],$_REQUEST[$deliveryData['messageId']])){
        $transId = $obj->getTransId();
        $msgId = $obj->getMsgId();        
        $customDB = $configData['custom_db'];
        $obj->getConnection(strtoupper($customDB['database_type']),$customDB['host'],$customDB['database'],$customDB['username'],$customDB['password'],$customDB['table']);        
        $schemaData = $obj->getSchemaData(" where account_type = '$accountType' limit 1");  
        $obj->closeDBConnection();
        $logger->info("Host info for this message located");
        if(sizeof($schemaData) != 0){
            foreach($schemaData as $hostData){
                if($hostData['status']){
                    $table = $hostData['table_name'];
                    $columns = unserialize($hostData['columns']);
                    $logger->info("Connecting to ".$hostData['host']);
                    $obj->getConnection($hostData['database_type'],$hostData['host'],$hostData['database_name'],$hostData['username'],$hostData['password'],$hostData['table_name']);
                    $obj->updateMessageTable("UPDATE $table set " . $columns['status'] . "='$status' , " . $columns['deliverytime'] . " =  '$timestamp' , " . $columns['cause'] . " = '$cause' where " . $columns['transactionid'] . " = '$transId' and " . $columns['phone'] . " = '$phone' and " . $columns['messageid']. " = '$msgId'");
                    //$obj->closeDBConnection(); 
                    $logger->info("Status Updated.." . "Phone: $phone | Delivery Time: $timestamp | Status: $status | Cause: $cause | causeId: $transId | messageId: $msgId");
                    if($status != $deliveryData['successStatusText']){
                        // retry if not delivered
                        $logger->info("Retry message sending to $phone");
                        $data = $obj->getMessages("select * from $table where " . $columns['transactionid'] . " = '$transId' and " . $columns['phone'] . " = '$phone' and " . $columns['messageid']. " = '$msgId' limit 1");                            
                        if(sizeof($data)>0){                            
                            $id = $data[0][$columns['id']];
                            $ph = $data[0][$columns['phone']];
                            $msgText = $data[0][$columns['message']];
                            $count = $data[0][$columns['attempt']];
                            //echo $attemptCount . " " . $count;
                            if($attemptCount > $count ){
                                $vendorData = $obj->getVendorData($hostData['rule_id']);                        
                                $logger->info("Message not delivered. Cause: $cause");
                                if(!$vendorData)
                                    $vendorData = "";
                                else
                                    $vendorData = serialize ($vendorData);
                                $ruleType = $obj->getRuleType();
                                if($vendorData == "" || $ruleType=="DEFAULT"){
                                // send using gupshupAPI                                                                                    
                                    require_once dirname(__FILE__).'/../resources/GupShup/Sender/Enterprise.php';
                                    $sender = new Sender_Enterprise('2000022337', 'ketan123');                                                                                     
                                    $sender->addMsg($ph,$msgText,$msgId);
                                    $logger->info("Sending message to $ph. Attemp number ".$coun+1);
                                    $response = $sender->sendMsg();		
                                    $processedTime = $obj->getCurrentTimestamp();  
                                    if($response->success){
                                        $logger->info("Message sent. API Response: " . $response->response); 
                                        $status = "INPROCESS";
                                    }else{
                                        $logger->error("Message sending error. API Response: " . $respone->error); 
                                        $status = "FAILED";
                                    }
                                    $obj->updateMessageTable("update $table set " . $columns['attempt'] . " = " . $columns['attempt'] . "+1," .$columns['status'] . " = '$status' , " . $columns['transactionid'] . " = '$response->transactionId' , " . $columns['processtime'] . " = '$processedTime' where " . $columns['id'] . " = '$id'");
                                    $logger->info("Message status updated to INPROCESS");   
                                }else{
                                    $logger->info("Some other vendor found.");
                                }                            
                            }else{
                                $logger->info("Number of attempts for this message exceed $attemptCount");
                            }
                        }
                    }else{
                        $logger->info("Message to $phone successfully delivered");
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
