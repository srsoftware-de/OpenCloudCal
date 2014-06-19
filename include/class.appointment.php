<?php

	class appointment {
		
		/* create new appointment object */
		/* TODO: load tags, urls and sessions */
		function __construct($id,$description,$start, $end,$coords){
			$this->id=$id;
			$this->description=$description;
			$this->start=$start;
			$this->end=$end;
			$this->coords=$coords;
		}
		
		function addTag($tag){
			global $db;
			if ($tag instanceof tag){
				$sql="INSERT INTO appointment_tags (tid,aid) VALUES ($tag->id, $this->id)";
				$db->query($sql);
			} else {
				$this->addTag(new tag($tag));
			}
		}
		
		/* loading all appointments, tags filter currently not implemented */
		/* TODO: implement tag filter */
		public static function loadAll($tags){
			global $db;
			$result=array();
			$sql="SELECT * FROM appointments";
			foreach ($db->query($sql) as $row){
				$result[]=new appointment($row['aid'],$row['description'],$row['start'],$row['end'],$row['coords']);
			}
			return $result;
		}
	}
?>