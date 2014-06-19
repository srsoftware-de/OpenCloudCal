<?php
	class tag {
		function __construct($tag){
			global $db;
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
		}
	}
	
?>