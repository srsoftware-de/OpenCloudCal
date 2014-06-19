<?php
	class tag {
		
		/* loads a tag, if the id is given (i.e. not 0), otherwise searches or creates the tag by its text */ 
		function __construct($id,$tag=''){
			global $db;
			if ($id==0){ // no id given => search or create
				$stm=$db->prepare("SELECT * FROM tags WHERE keyword=?");
				$stm->execute(array($tag));
				$results=$stm->fetchAll();
			
				if ($results){
					$this->id=$results[0]['tid'];
				} else {
					$stm=$db->prepare("INSERT INTO tags (keyword) VALUES (?)");
					$stm->execute(array($tag));
					$this->id=$db->lastInsertId();
				}
				$this->text=$tag;
			} else { //id given => load
				foreach ($db->query("SELECT keyword FROM tags WHERE tid=$id") as $row){
					$this->text=$row['keyword'];
					$this->id=$id;
					break;
				}
			}
		}
	}
	
?>
