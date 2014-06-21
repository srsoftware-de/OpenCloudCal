<?php
class url {

	/* loads a url, if the id is given (i.e. not 0), otherwise searches or creates the url by its text */
	function __construct(){
	}

	public static function create($address,$description=null){
		global $db;
		$instance=new self();

		$stm=$db->prepare("SELECT * FROM urls WHERE url=?");
		$stm->execute(array($address));
		$results=$stm->fetchAll();
		if ($results){
			$instance->id=$results[0]['uid'];
		} else {
			$stm=$db->prepare("INSERT INTO urls (url) VALUES (?)");
			$stm->execute(array($address));
			$instance->id=$db->lastInsertId();
		}
		if ($description==null){
			$instance->description=$address;
		} else {
			$instance->description=$description;
		}
		$instance->address=$address;
		return $instance;
	}

	public static function load($id){
		global $db;
		$instance=new self();
		foreach ($db->query("SELECT url FROM urls WHERE uid=$id") as $row){
			$instance->id=$id;
			$instance->url=$row['url'];
			$instance->description=null;
			break;
		}
		return $instance;
	}
}

?>
