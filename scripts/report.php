<?php

/**
 * Description of report
 *
 * @author Abhijit <abhijit@webaroo.com>
 */
$flag = true;
if(isset($_REQUEST['format']) && strtoupper($_REQUEST['format']) == 'DOWNLOAD'){ 
    header("Content-type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"report.csv\"");   
    $flag = false;
}
require_once dirname(__FILE__).'/../resources/log4php/Logger.php';
Logger::configure('../config/log.properties');
$logger = Logger::getLogger("Dispatcher.report");
require_once dirname(__FILE__).'/class/GenericClass.php';    
$accountType = strtoupper($_REQUEST['accountType']);
$from = ($_REQUEST['from']);
$to = $_REQUEST['to'];

if(isset($accountType) && $accountType != ""){
    $configData = parse_ini_file("../config/setup.properties",true);
    $timezone= $configData['timezone']['timezone'];    
    $columns = $configData['report'];    
    $customDB = $configData['custom_db'];
    $obj = new GenericClass($timezone);
    $from = strtotime($from)*1000;
    $to = strtotime($to)*1000;
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
                        $query .= " where " . $columns['stime'] ."  >= '$from'";                    
                    }
                    if($to != ''){
                        $to = strtotime($to)*1000;                
                        $query .= " where " . $columns['stime'] ." <= '$to'";
                    }
                    $ans = $obj->getMessages($query);                            
                    if(sizeof($ans)!=0){
                        $row=""; 
                        if($flag){
                            echo "<table border='1'><tr>";
                            foreach($columns as $key=>$value){
                                echo "<th>$value</th>";
                            }
                            echo "</tr>";
                        }else
                            echo $columnList . "\n";
                        foreach ($ans as $data) {
                            if($flag){
                                echo "<tr>";
                            }
                            foreach($columns as $key=>$value){
                                if(strpos($key, 'datetime'))
                                        $result = date('d-m-Y H:i:s',$data[$value]/1000);
                                else
                                    $result = $data[$value];
                                if($flag)
                                    echo "<td>$result</td>";
                                else
                                    $row .= $result . ",";
                            }
                            if($flag)
                                echo "</tr>";
                            else{
                                $row = substr($row, 0,-1);
                                echo $row . "\n";
                            }
                        }
                        if($flag)
                            echo "</table>";
                    }else{
                        die("data not found");
                    }            
                }
            }
        }    
    }
}
?>