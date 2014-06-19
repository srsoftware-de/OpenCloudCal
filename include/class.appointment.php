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
      if ($id==0){
        $this->save();
      } 
      $this->tags=$this->getTags();
      $this->urls=$this->getUrls();
      $this->sessions=$this->getSessions();
    }

    function save(){
      global $db;
      $sql="INSERT INTO appointments (description, start, end, coords) VALUES (:description, :start, :end, :coords)";
      $stm=$db->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
      $stm->execute(array(':description' => $this->description, ':start' => $this->start, ':end' => $this->end, ':coords' => $this->coords));
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
        $this->removeTag(tag::create($tag));
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
        die ("this is not an url"); // TODO: exception
      }
    }

    function removeUrlByAddress($address){
      $url=new url(0,'',$address);
      $this->removeUrl($url);
    }
    
    function removeUrlById($uid){
      $url=new url($uid,'');
      $this->removeUrl($url);
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
        $result[]=new appointment($row['aid'],$row['description'],$row['start'],$row['end'],$row['coords']);
      }
      return $result;
    }
  }
?>
