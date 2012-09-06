<?php

/**
 * Description of DB
 *
 * MSSQL Connector
 * 
 * @author Abhijit
 */

require_once dirname(__FILE__).'/../../resources/log4php/Logger.php';

class DB {    
      
      private $conn;
      private $logger;
      private $result;
      
      public function __construct($host,$hostName,$database,$username,$password)
      {                              
          Logger::configure('../../config/log.properties');
          $this->logger = Logger::getLogger("Dispatcher.MSSQL");
          $link = odbc_connect("Driver={SQL Server Native Client 10.0};Server=$host;Database=$database;",$username, $password);
          if (!$link) {              
              throw new Exception("MSSQL Database server could not be connected. Error: " . mssql_get_last_message() );
          }
          //$selected = mssql_select_db($database);
          //if(!$selected) {              
            //  throw new Exception("Cannot connect to $database DB. Error: ". mssql_get_last_message());
          //}       
          $this->conn= $link;
          return TRUE;
      }
      
      public function runQuery($query){
          $this->logger->info("Query: " . $query );
          try {                            
              $ans = odbc_exec( $this->conn,$query);
              $result = Array();
              if (!$ans) {                  
                  throw new Exception(odbc_error());
              }
          } catch(Exception $e) {
              throw $e;
          }
          
          $words = explode(" ", trim($query));
          $firstWord = strtoupper($words[0]);
          if ($firstWord == "SELECT") {
              while ($data = odbc_fetch_array($ans)) {
                  $result[] = $data;
              }
              $this->logger->info("Rows returned: " . sizeof($result));
              $this->result = $result;
          } else {
              $n = odbc_num_rows();
              $this->logger->info("Affected $n rows.");
              $this->result= $n;
          }
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
          try{
          $ans = mssql_query($query);
          while ($data = mssql_fetch_assoc($ans)) {
            $result[] = $data;
          }
          }catch(Exception $e){
              throw new Exception("MSSQL Query Error: ". $e->getMessage());
          }
          return $result;
      }
      public function getResult(){
          return $this->result;
      }

      public function closeCxn(){
          odbc_close($this->conn);
      }                         
}

  /*try{
    $db = new DB('dispatcher\sqlexpress','','dispatcher','webaroo','webar00');
    if($db){      
     $db->runQuery("select * from outgoing");
      var_dump($db->getResult());
	  echo "connected";
    }
    else{
      echo "ORACLE NOT CONNECTED";
    }
  }
  catch(Exception $e){
      print_r($e->getMessage());
 }  */


?>
