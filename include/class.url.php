<?php
class url {

	/* loads a url, if the id is given (i.e. not 0), otherwise searches or creates the url by its text */
	function __construct(){
	}

	public static function create($address,$description=null){
		$instance=new self();
		$pos = strpos($address, '?fbclid=');
		if ($pos !== false){
		    $address = substr($address, 0,$pos);
		}
		if ($description==null){
			$instance->description=$address;
		} else {
			$instance->description=$description;
		}
		$instance->address=$address;		
		return $instance;
	}

	function save(){
		global $db;
		if (startsWith($this->address, 'javascript')){
			return false;
		}		
		$stm=$db->prepare("SELECT * FROM urls WHERE url=?");
		$stm->execute(array($this->address));
		$results=$stm->fetchAll();
		if ($results){
			$this->id=$results[0]['uid'];
		} else {
			$stm=$db->prepare("INSERT INTO urls (url) VALUES (?)");
			$stm->execute(array($this->address));
			$this->id=$db->lastInsertId();
		}
		return true;	
	}

	public static function load($id){
		global $db;
		$stm=$db->prepare("SELECT url FROM urls WHERE uid=?");
		$stm->execute(array($id));
		$results=$stm->fetchAll();
		if ($results){			
			$instance=new self();
			$row=$results[0];
			$instance->id=$id;
			$instance->address=$row['url'];
			if (empty($instance->address)){
				return false;
			}
			$instance->description=null;
			return $instance;
		}
		return false;		
	}
}

?>
