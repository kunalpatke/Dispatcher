<?php

/**
 * Description of report
 *
 * @author Abhijit <abhijit@webaroo.com>
 */

if(isset($_REQUEST['format']) && strtoupper($_REQUEST['format']) == 'DOWNLOAD'){ 
    header("Content-type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"report.csv\"");    
}
require_once dirname(__FILE__).'/../resources/log4php/Logger.php';
Logger::configure('../config/log.properties');
$logger = Logger::getLogger("Dispatcher.report");
require_once dirname(__FILE__).'/class/GenericClass.php';    
$accountType = strtoupper($_REQUEST['accountType']);
$from = $_REQUEST['from'];
$to = $_REQUEST['to'];

if(isset($accountType) && $accountType != ""){
    $configData = parse_ini_file("../config/setup.properties",true);
    $timezone= $configData['timezone']['timezone'];
    //$reportData = $configData['report'];
    $columns = $configData['report'];
    //echo $columns;
    //echo "\n";
    $customDB = $configData['custom_db'];
    $obj = new GenericClass($timezone);
    $obj->getConnection(strtoupper($customDB['database_type']),$customDB['host'],$customDB['database'],$customDB['username'],$customDB['password'],$customDB['table']);        
    $schemaData = $obj->getSchemaData(" where account_type = '$accountType' limit 1");  
    $obj->closeDBConnection();
    if(sizeof($schemaData) != 0){
        foreach($schemaData as $hostData){
            if($hostData['status']){
                $table = $hostData['table_name'];                
                $obj->getConnection($hostData['database_type'],$hostData['host'],$hostData['database_name'],$hostData['username'],$hostData['password'],$hostData['table_name']);                    
                $columnList = $obj->getColumnList($columns);
                if($columnList){
                    $query = "select $columnList from ". $hostData['table_name'];
                    if($from != ''){                    
                        $from = strtotime($from)*1000;
                        $query .= " where SUBMITTEDTIME >= '$from'";                    
                    }
                    if($to != ''){
                        $to = strtotime($to)*1000;                
                        $query .= " where SUBMITTEDTIME <= '$to'";
                    }
                    $ans = $obj->getMessages($query);                            
                    if(sizeof($ans)!=0){
                        $row="";    
                        echo $columnList . "\n";
                        foreach ($ans as $data) {
                            foreach($columns as $key=>$value){
                                $row .= $data[$value] . ",";
                            }
                            $row = substr($row, 0,-1);
                            echo $row . "\n";
                        }   
                    }else{
                        die("data not found");
                    }            
                }
            }
        }    
    }
}
?>