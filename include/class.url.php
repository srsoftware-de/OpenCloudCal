<?php
	class url {
		
		/* loads a url, if the id is given (i.e. not 0), otherwise searches or creates the url by its text */ 
		function __construct($id,$description,$url=''){
			global $db;
                        $this->description=$description;

			if ($id==0){ // no id given => search or create
				$stm=$db->prepare("SELECT * FROM urls WHERE url=?");
				$stm->execute(array($url));
				$results=$stm->fetchAll();
			
				if ($results){
					$this->id=$results[0]['uid'];
				} else {
					$stm=$db->prepare("INSERT INTO urls (url) VALUES (?)");
					$stm->execute(array($url));
					$this->id=$db->lastInsertId();
				}
				$this->address=$url;
			} else { //id given => load
				foreach ($db->query("SELECT url FROM urls WHERE uid=$id") as $row){
					$this->url=$row['url'];
					$this->id=$id;
					break;
				}
			}
		}
	}
	
?>
