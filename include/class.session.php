<?php
  class session {
    
    /* loads a session, if the id is given (i.e. not 0), otherwise creates the session */ 
    function __construct(){
    }

    public static function create($description,$start,$end){
      global $db;
      $instance = new self();
      $sql="INSERT INTO sessions (description, start, end) VALUES (:description,:start,:end)";              
      $stm=$db->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));      
      $stm->execute(array(':description'=>$description,':start'=>$start,':end'=>$end));
      $instance->id=$db->lastInsertId();                  
      $instance->description=$description;                                       
      $instance->start=$start;                        
      $instance->end=$end;                       
      return $instance;
    }
    
    public static function load($id){
      global $db;
      $instance=new self(); 
      foreach ($db->query("SELECT * FROM sessions WHERE sid=$id") as $row){
        $instance->id=$id;
        $instance->description=$row['description'];
        $instance->start=$row['start'];
        $instance->end=$row['end'];
        break;
      }
      return $instance;
    }
  }
  
?>
