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
			$this->tags=$this->getTags();
		}
		
		function getTags(){
			global $db;
			$tags=array();
			$sql="SELECT tid FROM appointment_tags WHERE aid=$this->id";
			foreach ($db->query($sql) as $row){
				$tag=new tag($row['tid']);
				$tags[$tag->id]=$tag;
			}
			return $tags;
		}
		
		/* adds a tag to the appointment */
		function addTag($tag){
			global $db;
			if ($tag instanceof tag){
				$sql="INSERT INTO appointment_tags (tid,aid) VALUES ($tag->id, $this->id)";
				$db->query($sql);
				$this->tags[$tag->id]=$tag;
			} else {
				$this->addTag(new tag(0,$tag));
			}
		}
		
		/* remove tag from appointment */
		function removeTag($tag){
			global $db;
			if ($tag instanceof tag){
				$sql="DELETE FROM appointment_tags WHERE tid=$tag->id AND aid=$this->id";
				$db->query($sql);
				unset($this->tags[$tag->id]);
			} else {
				die ("this is not a tag"); // TODO: exception
			}
		}
		
		function removeTagByText($keyword){
			$tag=new tag(0,$keyword);
			$this->removeTag($tag);
		}
		
		function removeTagById($tid){
			$tag=new tag($tid);
			$this->removeTag($tag);
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