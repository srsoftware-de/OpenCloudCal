<?php

  class appointment {
    
    /* create new appointment object */
    /* TODO: load tags, urls and sessions */
    function __construct(){
			$urls=array();
			$sessions=array();    	
    }
    
    public static function create($title,$description,$start, $end,$location,$coords,$save=true){
      $instance=new self();
    	$instance->title=$title;
    	$instance->description=$description;
    	$instance->start=$start;
    	$instance->end=$end;
      $instance->location=$location;
      
      $c=explode(',',str_replace(' ', '', $coords));
      if (count($c)==2){
      	$instance->coords=array('lat'=>$c[0],'lon'=>$c[1]);
      } else {
      	$instance->coords=false;
      }
      if ($save){      
    		$instance->save();
      }
      return $instance;
    }
    
    function tagLinks(){
    	$result="";
    	foreach ($this->tags as $tag){
    		$result.='<a href="?tag='.$tag->text.'">'.$tag->text.'</a> '.PHP_EOL;
    	}
    	return $result;
    	 
    }
    
    function mapLink(){
    	if ($this->coords){
    		return 'http://www.openstreetmap.org/?mlat='.$this->coords['lat'].'&mlon='.$this->coords['lon'].'&zoom=15';
    	}
    	return false;
    }
    
    public static function load($id){
    	global $db;
    	$instance=new self();    	 
    	$sql="SELECT * FROM appointments WHERE aid=$id";
    	foreach ($db->query($sql) as $row){
    		$instance=self::create($row['title'], $row['description'], $row['start'], $row['end'], $row['location'],$row['coords'],false);
    		$instance->id=$id;
    		$instance->loadRelated();
    		return $instance;
    	}    	 
    }
    
    /* loads tags, urls and sessions related to the current appointment */
    function loadRelated(){
    	$this->urls=$this->getUrls();
    	$this->tags=$this->getTags();
    	$this->sessions=session::loadAll($this->id);
    }
    
    function delete($id=false){
    	global $db;
    	if (!$id){
    		return;
    	}
    	
    	$sql = "DELETE FROM sessions WHERE aid=:id";
    	$stm=$db->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
    	$stm->execute(array(':id'=>$id));
    	 
    	$sql = "DELETE FROM appointments WHERE aid=:id";
    	$stm=$db->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
    	$stm->execute(array(':id'=>$id));
    }
  
    function save(){
      global $db;
      if ($this->coords){
      	$coords=implode(',', $this->coords);      	
      } else {
      	$coords=null;
      }
      if (isset($this->id)){
      	$sql="UPDATE appointments SET title=:title,description=:description, start=:start, end=:end, location=:location, coords=:coords WHERE aid=:id";
      	$stm=$db->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
      	$stm->execute(array(':title'=>$this->title,':description' => $this->description, ':start' => $this->start, ':end' => $this->end, ':location' => $this->location,':coords' => $coords,':id'=>$this->id));
      } else {
      	$sql="INSERT INTO appointments (title,description, start, end, location, coords) VALUES (:title,:description, :start, :end, :location, :coords)";      	
      	$stm=$db->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));      
      	$stm->execute(array(':title'=>$this->title,':description' => $this->description, ':start' => $this->start, ':end' => $this->end, ':location' => $this->location,':coords' => $coords));
      	$this->id=$db->lastInsertId();
      }
    }

    /******* TAGS ****************/
    
    function getTags(){
      global $db;
      $tags=array();
      $sql="SELECT tid FROM appointment_tags WHERE aid=$this->id";
      foreach ($db->query($sql) as $row){
        $tag=tag::load($row['tid']);
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
      	if (empty($tag)){
      		return;
      	}
        $this->addTag(tag::create($tag));
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
        if (is_int($tag)){
          $this->removeTag(tag::load($tag));
        } else {
          $this->removeTag(tag::create($tag));
        }
      }
    }
    
    function removeAllTags(){
    	global $db;
    	$sql="DELETE FROM appointment_tags WHERE aid=$this->id";
    	$db->query($sql);
    }
    
    /****** TAGS **************/
    /****** URLS **************/

    function getUrls(){
      global $db;
      $urls=array();
      $sql="SELECT uid,description FROM appointment_urls WHERE aid=$this->id";
      foreach ($db->query($sql) as $row){
        $url=url::load($row['uid']);
        if ($url){
        	$url->description=$row['description'];
        	$urls[$url->id]=$url;
        }
      }
      return $urls;
    }
    
    /* adds a url to the appointment */
    function addUrl($url){
      global $db;
      if ($url instanceof url){
        $stm=$db->prepare("INSERT INTO appointment_urls (uid,aid,description) VALUES (:uid, :aid, :description)", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
				$stm->execute(array(':uid'=>$url->id,':aid'=>$url->aid,':description'=>$url->description));
        $this->urls[$url->id]=$url;
      } else {
        $url=url::create($this->id,$url,$description);
        $this->addUrl($url);
      }
    }

    /* remove url from appointment */
    function removeUrl($url){
      global $db;
      if ($url instanceof url){
        $sql="DELETE FROM appointment_urls WHERE uid=$url->id AND aid=$this->id";
        $db->query($sql);
        unset($this->urls[$url->id]);
      } else {
        if (is_int($url)){
          $this->removeUrl(url::load($url));
        } else {
          $this->removeUrl(url::create($url));
        }
      }
    }

    /********* URLs ***********/
    /* loading all appointments */
    public static function loadAll($tags=null){
      global $db,$limit;
      $appointments=array();
      
      if ($tags!=null){
				if (!is_array($tags)){
					$tags=array($tags);
				}
				$sql="SELECT * FROM appointments NATURAL JOIN appointment_tags NATURAL JOIN tags WHERE keyword IN (?) ORDER BY start";
    		if ($limit){
    			$sql.=' LIMIT :limit';
    		}
    		$stm=$db->prepare($sql);
    		$stm->bindValue(':tags', reset($tags));
				
      } else {
      	$sql="SELECT * FROM appointments ORDER BY start";
    		if ($limit){
    			$sql.=' LIMIT :limit';
    		}    		
    		$stm=$db->prepare($sql);
      }
      if ($limit){
      	$stm->bindValue(':limit', $limit,PDO::PARAM_INT);
      }
      $stm->execute();
      $results=$stm->fetchAll();
      foreach ($results as $row){
      	$appointment=self::create($row['title'], $row['description'], $row['start'], $row['end'], $row['location'],$row['coords'],false	);
      	$appointment->id=$row['aid'];
      	$appointment->loadRelated();      	 
        $appointments[$appointment->id]=$appointment;
      }
      return $appointments;
    }
    
    public static function loadCurrent($tags=null){
    	global $db,$db_time_format,$limit;
    	$appointments=array();
    	
    	$now=date($db_time_format);
    
    	if ($tags!=null){
    		if (!is_array($tags)){
    			$tags=array($tags);
    		}
    		$sql="SELECT * FROM appointments NATURAL JOIN appointment_tags NATURAL JOIN tags WHERE start>'$now' AND keyword IN (:tags) ORDER BY start";
    		if ($limit){
    			$sql.=' LIMIT :limit';
    		}
    		$stm=$db->prepare($sql);
    		$stm->bindValue(':tags', reset($tags));
    	} else {
    		$sql="SELECT * FROM appointments WHERE start>'$now' ORDER BY start";
    		if ($limit){
    			$sql.=' LIMIT :limit';
    		}    		
    		$stm=$db->prepare($sql);
    	}
    	if ($limit){
    		$stm->bindValue(':limit', $limit,PDO::PARAM_INT);    			
    	}
    	$stm->execute();    		    		
    	$results=$stm->fetchAll();
    	foreach ($results as $row){
    		$appointment=self::create($row['title'], $row['description'], $row['start'], $row['end'], $row['location'],$row['coords'],false	);
    		$appointment->id=$row['aid'];
    		$appointment->loadRelated();
    		$appointments[$appointment->id]=$appointment;
    	}
    	return $appointments;
    }
    
    function tags($separator){
    	$res=array();
    	foreach ($this->tags as $tag){
    		$res[]=$tag->text;
    	}
    	return implode($separator, $res);
    }
  }
?>
