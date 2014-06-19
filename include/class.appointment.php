<?php

	class appointment {
		
		function __construct($id,$description,$start, $end,$coords){
			$this->id=$id;
			$this->description=$description;
			$this->start=$start;
			$this->end=$end;
			$this->coords=$coords;
		}
		
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