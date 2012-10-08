<?php
/**
 * Description of report
 *
 * @author Abhijit <abhijit@webaroo.com>
 */
error_reporting(0);
require_once dirname(__FILE__).'/../resources/log4php/Logger.php';
Logger::configure('../config/log.properties');
$logger = Logger::getLogger("Dispatcher.report");
require_once dirname(__FILE__).'/class/GenericClass.php';    
$accountType = strtoupper($_REQUEST['accountType']);
$from = ($_REQUEST['from']);
$to = $_REQUEST['to'];

if(isset($accountType) && $accountType != ""){
    $logger->info("Generating report for account type: $accountType");
    $configData = parse_ini_file("../config/setup.properties",true);
    $timezone= $configData['timezone']['timezone'];    
    $columns = $configData['reportCols'];    
    $customDB = $configData['custom_db'];
    $obj = new GenericClass($timezone);
    $from = strtotime($from)*1000;
    $to = strtotime($to)*1000;
    $obj->getConnection(strtoupper($customDB['database_type']),$customDB['host'],$customDB['database'],$customDB['username'],$customDB['password'],$customDB['table']);        
    $schemaData = $obj->getSchemaData(" where account_type = '$accountType' limit 1");  
    $obj->closeDBConnection();
    $file = "../reports/Report-" . date('Y-m-d H:i:s'). ".csv";
    $logger->info("File created: $file");
    $handle = fopen($file, 'w');
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
                        $row = $columnList . "\n";
                        foreach ($ans as $data) {                            
                            foreach($columns as $key=>$value){
                                if(strpos($key, 'datetime'))
                                        $result = date('d-m-Y H:i:s',$data[$value]/1000);
                                else
                                    $result = $data[$value];                               
                                $row .= $result . ",";
                            }                            
                            $row = substr($row, 0,-1);                                                    
                        }
                        fwrite($handle, $row);
                        fclose($handle);
                        $logger->info("Report format: " . $_REQUEST['format']);
                        if (strtoupper($_REQUEST['format']) == 'EMAIL'){
                            $emailProperties = $configData['reportEmail'];                            
                            if(filesize($file) > $emailProperties['maxfilesize']){
                                $logger->info("Filesize grater than maximum allowed for email:  " . filesize($file));
                                $_REQUEST['format'] = "DOWNLOAD";
                                break;
                            }                              
                            require_once dirname(__FILE__).'/../resources/EmailSender.class.php';
                            $files = array($file);    
                            $head = array(
                            'to'   =>array($emailProperties['toAddress']=>$emailProperties['toName']),
                            'from'    =>array($emailProperties['fromAddress']=>$emailProperties['fromName']));
                            $subject = $emailProperties['subject'];                                                        
                            $body = "Please find attached SMS report";                            
                            if(EmailSender::send($head,$subject,$body, $files)){
                                $logger->info("Email sent to : " . $emailProperties['toAddress']);
                            }
                            else{                                
                               $logger->error("Email failed");
                           }  
                        }
                        if(isset($_REQUEST['format']) && strtoupper($_REQUEST['format']) == 'DOWNLOAD'){ 
                            header("Content-type: application/octet-stream");
                            header("Content-Disposition: attachment; filename=$file");   
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