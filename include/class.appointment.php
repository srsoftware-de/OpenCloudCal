<?php

  class appointment {
    
    /* create new appointment object */
    /* TODO: load tags, urls and sessions */
    function __construct(){
    	
    }
    
    private static function create_internal($title,$description,$start, $end,$coords){
    	$instance=new self();
    	$instance->title=$title;
    	$instance->description=$description;
    	$instance->start=$start;
    	$instance->end=$end;
    	$instance->coords=$coords;
    	return $instance;    	 
    }
    
    public static function create($title,$description,$start, $end,$coords){
    	$instance=self::create_internal($title, $description, $start, $end, $coords);
      $instance->save();
      return $instance;
    }
    
    public static function load($id){
    	$instance=new self();    	 
    	$sql="SELECT * FROM appointments WHERE aid=$id";
    	foreach ($db->query($sql) as $row){
    		$instance=self::create_internal($row['title'], $row['description'], $row['start'], $row['end'], $row['coords']);
    		$instance->id=$id;
    		$instance->loadRelated();
    		return $instance;
    	}    	 
    }
    
    /* loads tags, urls and sessions related to the current appointment */
    function loadRelated(){
    	$this->urls=$this->getUrls();
    	$this->tags=$this->getTags();
    	$this->sessions=$this->getSessions();
    }

    function save(){
      global $db;
      $format='Y-m-d H:i:0';
      $start=date($format,$this->start);
      $end=date($format,$this->end);
      $sql="INSERT INTO appointments (title,description, start, end, coords) VALUES (:title,:description, :start, :end, :coords)";
      $stm=$db->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
      $stm->execute(array(':title'=>$this->title,':description' => $this->description, ':start' => $start, ':end' => $end, ':coords' => $this->coords));
      $this->id=$db->lastInsertId();
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
    
    /****** TAGS **************/
    /****** URLS **************/

    function getUrls(){
      global $db;
      $urls=array();
      $sql="SELECT uid,description FROM appointment_urls WHERE aid=$this->id";
      foreach ($db->query($sql) as $row){
        $url=url::load($row['uid']);
        $url->description=$row['description'];

        $urls[$url->id]=$url;
      }
      return $urls;
    }
    
    /* adds a url to the appointment */
    function addUrl($url,$description=null){
      global $db;
      if ($url instanceof url){
        if ($description==null){
          $description=$url->description;
        }
        $stm=$db->prepare("INSERT INTO appointment_urls (uid,aid,description) VALUES ($url->id, $this->id, ?)");
                                $stm->execute(array($description));
        $this->urls[$url->id]=$url;
      } else {
        $this->addUrl(url::create($url,$description));
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
    /********* SESSIONs *******/

    function getSessions(){
      global $db;
      $sessions=array();
      $sql="SELECT sid FROM appointment_sessions WHERE aid=$this->id";
      foreach ($db->query($sql) as $row){
        $session=session::load($row['sid']);
        $sessions[$session->id]=$session;
      }
      return $sessions;
    }

    /* adds a session to the appointment */
    function addSession($session,$start=null,$end=null){
      global $db;
      if ($session instanceof session){
        $sql="INSERT INTO appointment_sessions (sid,aid) VALUES ($session->id, $this->id)";
        $db->query($sql);
        $this->sessions[$session->id]=$session;
      } else {
        $this->addSession(session::create($session,$start,$end));
      }
    }

    /* remove session from appointment */
    function removeSession($session){
      global $db;
      if ($session instanceof session){
        $sql="DELETE FROM appointment_sessions WHERE sid=$session->id AND aid=$this->id";
        $db->query($sql);
        unset($this->sessions[$session->id]);
      } else {
        if (is_int($session)){
          $this->removeSession(session::load($session));
        } else {
          die("can only remove sessions referenced by handle or id");
        }
      }
    }


    /* loading all appointments, tags filter currently not implemented */
    /* TODO: implement tag filter */
    public static function loadAll($tags=''){
      global $db;
			if (!is_array($tags)){
				if ($tags=''){
					$tags=array();
				} else {
					$tags=array($tags);
				}
			}
      $result=array();
      $sql="SELECT * FROM appointments";
      foreach ($db->query($sql) as $row){
      	$instance=self::create_internal($row['title'], $row['description'], $row['start'], $row['end'], $row['coords']);
      	$instance->id=$row['aid'];
      	$instance->loadRelated();      	 
        $result[]=$instance;
      }
      return $result;
    }
  }
?>
