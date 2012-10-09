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
$accountType = isset($_REQUEST['actType']) ? ($_REQUEST['actType'] != '') ? strtoupper($_REQUEST['actType']):NULL:NULL;
$from = isset($_REQUEST['from']) ? ($_REQUEST['from'] != '') ? $_REQUEST['from']:NULL:NULL;
$to = isset($_REQUEST['to']) ? ($_REQUEST['to'] != '') ? $_REQUEST['to']:NULL:NULL;
$status = isset($_REQUEST['status']) ? ($_REQUEST['status'] != '') ? strtoupper($_REQUEST['status']):NULL:NULL;
$format = isset($_REQUEST['format']) ? ($_REQUEST['format'] != '') ? strtoupper($_REQUEST['format']):"DOWNLOAD":"DOWNLOAD";
if(!is_null($accountType)){
    $logger->info("Generating report for account type: $accountType");
    $configData = parse_ini_file("../config/setup.properties",true);
    $timezone= $configData['timezone']['timezone'];    
    $columns = $configData['reportCols'];    
    $customDB = $configData['custom_db'];
    $obj = new GenericClass($timezone);    
    $obj->getConnection(strtoupper($customDB['database_type']),$customDB['host'],$customDB['database'],$customDB['username'],$customDB['password'],$customDB['table']);        
    $schemaData = $obj->getSchemaData(" where account_type like '$accountType' limit 1");  
    $obj->closeDBConnection();
    $file = "../reports/Report#".date("s").".csv";
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
                    if(!is_null($from) || !is_null($to) || !is_null($status) ){
                        $query .= " where ";
                        if(!is_null($from)){                    
                            $from = strtotime($from)*1000;
                            $query .= $columns['sdatetime'] ."  >= '$from'";                    
                        }
                        if(!is_null($to)){
                            $to = strtotime($to)*1000; 
                            if(!is_null($from))
                                $query .= " && " ;
                            $query .= $columns['sdatetime'] ." <= '$to'";                            
                        }
                        if(!is_null($status)){
                            if(!is_null($from) || !is_null($to))
                                $query .= " && " ;
                            $query .= $columns['status'] ." like '$status'";
                        }
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
                            $row .= "\n";
                        }
                    }                    
                    fwrite($handle, $row);
                    fclose($handle);
                    $logger->info("Report format: " . $format);
                    if ($format == 'EMAIL'){
                        $emailProperties = $configData['reportEmail'];      
                        $size = filesize($file);
                        if($size < $emailProperties['maxfilesize']*1024*1024){                                            
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
                        else{
                            $logger->info("File size grater than maximum allowed size for email: $size");
                            //echo "<script type='text/javascript'> alert('File size greater than maximum allowed size for email: $size');</script>";
                            $format = "DOWNLOAD";
                        }
                    }
                    if($format == 'DOWNLOAD'){ 
                        $logger->info("Downloading file");
                        if (file_exists($file))
                        {
                            if(false !== ($hanlder = fopen($file, 'r')))
                            {
                                header('Content-Description: File Transfer');
                                header('Content-Type: application/octet-stream');
                                header('Content-Disposition: attachment; filename='.basename($file));
                                header('Content-Transfer-Encoding: chunked'); //changed to chunked
                                header('Expires: 0');
                                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                                header('Pragma: public');        
                                while(false !== ($chunk = fread($handler,4096)))
                                {
                                    echo $chunk;
                                }
                            }
                            exit;
                        }else{
                            $logger->error("File does not exist");
                        }

//                        $logger->info("Report format: " . $format);
//                        $path_parts = pathinfo($file);
//                        header("Content-type: application/octet-stream");
//                        header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\"");   
//                        header("Content-length: $size");
                    }                                            
                }
            }
        }   
    }
}
?>