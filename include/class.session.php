<?php
  class session {
    
    /* loads a session, if the id is given (i.e. not 0), otherwise creates the session */ 
    function __construct(){
    }

    public static function create($aid,$description,$start,$end){
      global $db;
      $instance = new self();
      $sql="INSERT INTO sessions (description, aid, start, end) VALUES (:description, :aid, :start,:end)";              
      $stm=$db->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));      
      $stm->execute(array(':description'=> $description,':aid'=>$aid,':start'=> $start,':end'=> $end));
      $instance->id=$db->lastInsertId();     
      $instance->aid=$aid;             
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
    
    public static function loadAll($aid){
    	global $db;
    	$sessions=array();
    	foreach ($db->query("SELECT * FROM sessions WHERE aid=$aid") as $row){
    		$instance=new self();
    		$instance->id=$row['sid'];
    		$instance->aid=$aid;
    		$instance->description=$row['description'];
    		$instance->start=$row['start'];
    		$instance->end=$row['end'];
    		$sessions[]=$instance;
    	}
    	return $sessions;
    }
    
    function delete($sid=false){
    	global $db;
    	if (!$sid){
    		return;
    	}
    	$sql = "DELETE FROM sessions WHERE sid=:sid";
    	$stm=$db->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
    	$stm->execute(array(':sid'=>$sid));
    
    }
    
  }
  
?>
