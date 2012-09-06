<?php

/**
 * Description of Run.php
 * 
 * This script will be invoked from a batch file which will then start the dispatcher process
 * 
 *It will read database type(MYSQL/ORACLE/MSSQL etc..) supported from a config file and create an appropriate connector.
 *
 * @author Abhijit <abhijit@webaroo.com>
 */
set_time_limit(0);
ini_set('memory_limit','1G');
require_once dirname(__FILE__).'/../resources/log4php/Logger.php';
require_once dirname(__FILE__).'/../resources/AsyncCall.class.php';
require_once dirname(__FILE__).'/class/GenericClass.php';
Logger::configure('../config/log.properties');
$logger = Logger::getLogger("Dispatcher.run");
$logger->info("Fetching config data");
$configData = parse_ini_file("../config/database.properties",true);
$customDB = $configData['custom_db'];
if(is_array($configData)){
    $logger->info("Host found for schema_info table");
    $databaseType = strtoupper($customDB['database_type']);
    $host= $customDB['host'];
    $hostName=$customDB['host_name'];
    $db = $customDB['database'];
    $userName = $customDB['username'];
    $password = $customDB['password'];    
    
    // connect to the host and get all message hosts info
    require_once dirname(__FILE__).'/../DBConnectors/'.$databaseType.'/DB.php';
    try{
        $logger->info("Fetch host info from schema_info table");
        $obj = new GenericClass();        
        $obj->getConnection($databaseType,$host,$hostName,$db,$userName,$password);
        $schemaData = $obj->getSchemaData();  
        //while(1){
            foreach($schemaData as $hostData){
                // if status is true, call async connector.php script and pass the data with it.
                if($hostData['status']){
                    $logger->info("Async call to: " . $hostData['host']);
                    $path = pathinfo($_SERVER['SCRIPT_NAME']);
                    $url = "http://" .$_SERVER['SERVER_NAME']  . $path['dirname'] . "/getMessages.php";   
                    // get vendor info based on ruleId
                    $vendorData = $obj->getVendorData($hostData['rule_id']);
                    if(!$vendorData)
                        $vendorData = "";
                    else
                        $vendorData = serialize ($vendorData);
                    $ruleType = $obj->getRuleType();
                    $params=array("host"=>$hostData['host'],"database_type"=>$hostData['database_type'],"db"=>$hostData['database_name'],"username"=>$hostData['username'],"password"=>$hostData['password'],"priority"=>$hostData['priority'],"table"=>$hostData['table_name'],"query"=>$hostData['query'],"ruleType"=>$ruleType,"vendorData"=>$vendorData);                        
                    AsyncCall::curl_post_async($url,$params);
                    $logger->info("Async call made to: " . $hostData['host']);
                }else{
                    $logger->info("Host down: " . $hostData['host']);
                }
            }
            //$obj->closeDBConnection();
            sleep(5);
            $logger->info("\n\n\n-------CALLING AGAIN--------\n\n\n");
        //}
    }
    catch(Exception $e){        
        $logger->error("Exception: " . $e->getMessage());
    }
}else{
    $logger->error("Hosts data not found. Please make entry in database.properties file");
}
?>
