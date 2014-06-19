<?php
  class tag {
    
    /* loads a tag, if the id is given (i.e. not 0), otherwise searches or creates the tag by its text */ 
    function __construct(){
                }

                public static function create($tag){
                  global $db;
                        $instance=new self();
      $stm=$db->prepare("SELECT * FROM tags WHERE keyword=?");
      $stm->execute(array($tag));
      $results=$stm->fetchAll();
      if ($results){
        $instance->id=$results[0]['tid'];
      } else {
        $stm=$db->prepare("INSERT INTO tags (keyword) VALUES (?)");
        $stm->execute(array($tag));
        $instance->id=$db->lastInsertId();
      }
      $instance->text=$tag;
                        return $instance;
                }

                public static function load($id){
                        global $db;
                        $instance=new self();
            foreach ($db->query("SELECT keyword FROM tags WHERE tid=$id") as $row){                                
        $instance->text=$row['keyword'];
        $instance->id=$id;
        break;
      }
                        return $instance;
    }
  }
  
?>
