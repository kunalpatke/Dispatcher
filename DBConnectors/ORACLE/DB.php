<?php

/**
 * Description of DB
 * 
 * ORACLE Connector
 * 
 * @author Abhijit <abhijit@webaroo.com>
 */

require_once dirname(__FILE__).'/../../resources/log4php/Logger.php';

class DB{
      
      private $conn;
      private $logger;
      private $result;
      
      public function __construct($host,$database,$username,$password)
      {                              
          Logger::configure('../../config/log.properties');
          $this->logger = Logger::getLogger("Dispatcher.ORACLEDB");
          $link = oci_connect($username, $password, $host.'/'.$database);
          if (!$link) {              
              throw new Exception("Database server could not be connected. Error: " . implode('|',oci_error()));
          }               
          $this->conn= $link;
          return TRUE;
      }
      
      public function runQuery($query){
          $this->logger->info("Query: " . $query );  
          $result=array();
          try{
            $stid = oci_parse($this->conn, $query);
            if (!oci_execute($stid)) {                  
                 throw new Exception(implode('|',oci_error()));
            }

            $words = explode(" ", trim($query));
            $firstWord = strtoupper($words[0]);
            if ($firstWord == "SELECT") {                                            
                while ($data = oci_fetch_assoc($stid)) {                  
                    $result[] = $data;
                }          
                $this->logger->info("Rows returned: " . sizeof($result));
                $this->result = $result;
            } else {
                $n = oci_num_rows($stid);
                $this->logger->info("Affected $n rows.");
                $this->result= $n;
            }
          }catch(Exception $e){
              throw $e;
          }
      }    
      
      public function getResult(){
          return $this->result;
      }
      
      public function selectQuery($table,$columns=array(),$condition=""){
          $result = array();
          $query = "select";
          if(sizeof($columns) == 0){
              $query .= " * ";              
          }else{
              foreach($columns as $col)
                $query .= " $col,";
              $query = substr($query, 0,-1);
          }
          $query .= " from $table ";
          if($condition != "")
              $query .= " where $condition";
          
          $stid = oci_parse($this->conn, $query);
                    
          if (!oci_execute($stid)) {                  
            throw new Exception(implode('|',oci_error()));
          }
          while ($data = oci_fetch_assoc($stid)) {
            $result[] = $data;
          }
          return $result;
      }
      
      public function closeCxn(){
          oci_close($this->conn);
      }                       
  }
//  try{
//    $db = new DB('Abhijit','','XE','webaroo','webar00');
//    if($db){      
//      $db->runQuery("select * from outgoing");
//      var_dump($db->getResult());
//    }
//    else{
//      echo "ORACLE NOT CONNECTED";
//    }
//  }
//  catch(Exception $e){
//      print_r($e->getMessage());
//  }  
?>
