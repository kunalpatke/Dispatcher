<?php

/**
 * Description of DB
 * 
 * MYSQL Connector
 *
 * @author Abhijit <abhijit@webaroo.com>
 */
require_once dirname(__FILE__).'/../../resources/log4php/Logger.php';
 class DB{
      
      private $conn;
      private $logger;
      private $result;
      
      public function __construct($host,$hostName,$database,$username,$password)
      {                              
          Logger::configure('../../config/log.properties');
          $this->logger = Logger::getLogger("Dispatcher.MYSQLDB");
          $link = mysql_connect($host, $username, $password);
          if (!$link) {              
              throw new Exception("Database server could not be connected. Error: ". mysql_error());
          }
          $selected = mysql_select_db($database);
          if(!$selected) {              
              throw new Exception("Cannot connect to $database DB. Error: ". mysql_error());
          }            
          $this->conn= $link;
          return TRUE;
      }
      
      public function selectQuery($table,$columns=array(),$condition=""){          
          $result = array();
          $query = "select";          
          if(sizeof($columns) == 0){
              $query .= " * ";              
          }else{
              foreach($columns as $col)
                $query .= " $col,";
              //$query = substr($query, 0,-1);
          }
          $query .= " from $table ";
          
          if($condition != "")
              $query .= " where $condition";          
          $this->logger->info("Query: " . $query );
          $ans = mysql_query($query);
          if (!$ans) {                  
            throw new Exception(mysql_error());
          }
          while ($data = mysql_fetch_assoc($ans)) {
            $result[] = $data;
          }
          $this->logger->info("Result: " .var_dump($result));
          return $result;
      }
      
      public function closeCxn(){
          mysql_close($this->conn);
      }
      
      public function getResult(){
          return $this->result;
      }
      
      
      public function runQuery($query){
          $this->logger->info("Query: " . $query );
          try {                            
              $ans = mysql_query($query);
              $result = Array();
              if (!$ans) {                  
                  throw new Exception(mysql_error());
              }
          } catch(Exception $e) {
              throw $e;
          }
          
          $words = explode(" ", trim($query));
          $firstWord = strtoupper($words[0]);
          if ($firstWord == "SELECT") {
              while ($data = mysql_fetch_assoc($ans)) {
                  $result[] = $data;
              }
              $this->logger->info("Rows returned: " . sizeof($result));
              $this->result = $result;
          } else {
              $n = mysql_affected_rows();
              $this->logger->info("Affected $n rows.");
              $this->result= $n;
          }
      }      
  }
?>
