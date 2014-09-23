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

      $sql = "DELETE FROM appointment_tags WHERE aid=:id";
      $stm=$db->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
      $stm->execute(array(':id'=>$id));

      $sql = "DELETE FROM appointment_urls WHERE aid=:id";
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
    
    function sendToGrical(){
    	die('sendToGrical called!');
    	if (is_callable('curl_init')){
    		$text ='title: '.$this->title.PHP_EOL;
    		$text.='start: '.substr($this->start, 0,10).PHP_EOL; // depends on db_time_format set in init.php
    		$text.='starttime: '.substr($this->start, 11,5).PHP_EOL; // depends on db_time_format set in init.php
    		$text.='end: '.substr($this->end, 0,10).PHP_EOL; // depends on db_time_format set in init.php
    		$text.='endtime: '.substr($this->end, 11,5).PHP_EOL; // depends on db_time_format set in init.php
    		$text.='tags: opencloudcal';
    		if (isset($this->tags) && !empty($this->tags)){
	    		foreach ($this->tags as $tag){
	    			if (!preg_match('/[^A-Za-z0-9-]/', $tag->text)){ // only chars, numbers and dashes allowed in grical
	    				$text.=' '.$tag->text;	    				 
	    			}
    			}
    		}
    		$text.=PHP_EOL;
    		$text.='urls:'.PHP_EOL;
    		if (isset($this->urls) && !empty($this->urls)){
    			foreach ($this->urls as $url){
    				$text.='    '.$url->description.' '.$url->address.PHP_EOL;
    			}
    		}
    		$text.='    posted from http'.(isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}?show=$this->id".PHP_EOL;
    		if ($this->coords){
    			$text.='coordinates: '.$this->coords['lat'].', '.$this->coords['lon'].PHP_EOL;
    			$text.='exact: True'.PHP_EOL;
    		}
				$text.='address: '.$this->location.PHP_EOL;
    		$text.='description:'.PHP_EOL;
    		$text.=$this->description;

    		$ckfile = "/tmp/gricalcookie";
    		$target_host = "https://grical.org/";
    		$target_request = "e/new/raw/";
    	
    		// 2. Visit homepage to set cookie
    		$ch = curl_init ($target_host);
    	
    		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0); // TODO: disabling host an peer check is rather bad.
    		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0); // this should be implemented in the future
    	
    		curl_setopt ($ch, CURLOPT_COOKIEJAR, $ckfile); // prepare to recieve cookie
    		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true); // answer of request shall be returned
    		$output = curl_exec ($ch); // perform request, answer goes to variable
    	
    		/* the next lines extract the csrf token */
    		$output=explode("input", curl_exec($ch));
    		foreach ($output as $line){
    			if (strpos($line, "csrfmiddlewaretoken")){
    				$tokens=explode(" ", $line);
    				foreach ($tokens as $token){
    					if (strpos($token, "alue=")){
    						$token=substr($token, 7,-1);
    						break;
    					}
    				}
    				break;
    			}
    		}
    	
    		curl_close($ch);
    	
    		$form=array();
    		$form['event_astext']=$text;    		
    		$form['csrfmiddlewaretoken']=$token; // add the token to the input for the actual post request
    		$post_data=http_build_query($form); // format query data
    	
    		// 3. Continue
    		$login = curl_init ($target_host.$target_request);
    	
    		curl_setopt ($login, CURLOPT_SSL_VERIFYHOST, 0); // TODO: disabling host an peer check is rather bad.
    		curl_setopt ($login, CURLOPT_SSL_VERIFYPEER, 0); // this should be implemented in the future
    	
    		curl_setopt($login, CURLOPT_COOKIESESSION, 1); // use cookies
    		curl_setopt($login, CURLOPT_COOKIEJAR, $ckfile); // prepare to recieve cookies
    		curl_setopt($login, CURLOPT_COOKIEFILE, $ckfile); // prepare to submit cookies
    		curl_setopt($login, CURLOPT_TIMEOUT, 40);
    		curl_setopt($login, CURLOPT_RETURNTRANSFER, 1); // answer of request shall be returned
    		curl_setopt($login, CURLOPT_HEADER, 1); // send header
    		curl_setopt($login, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // set user agent
    		curl_setopt($login, CURLOPT_FOLLOWLOCATION, 1); // follow redirects
    		curl_setopt($login, CURLOPT_POST, 1); // prepare for sending data
    		curl_setopt($login, CURLOPT_POSTFIELDS, $post_data); // provide data
    		$reply=curl_exec($login); // acutally send data
    		$handle=fopen('reply.html', 'w');
    		fwrite($handle, $reply);
    		fclose($handle);
    		curl_close($login);
    		if (strpos($reply, "event saved")){
    			return true;
    		} else {
    			warn(str_replace('%server', $target_host, loc('Sorry, I was not able to save event to %server.')));
    		}
    	} else {
    		warn(str_replace('%server',$target_host,loc('Sorry, curl not callable. This means I am not allowed to send the event to %server.')));
    	}
    	return false;
    } // sendToGrical
    
    function sendToCalcifer(){
    	if (is_callable('curl_init')){
				$url='https://calcifer-test.datenknoten.me/termine/';
				$formfields=array();
				$formfields['startdate']=substr($this->start, 0,16);
				$formfields['enddate']=substr($this->end, 0,16);
				$formfields['summary']=$this->title;
				$formfields['description']=$this->description;
				$formfields['location']=$this->location;
				if ($this->coords){
					$formfields['location_lat']=$this->coords['lat'];
					$formfields['location_lon']=$this->coords['lon'];
				}
				$formfields['tags']='OpenCloudCal';
				if (isset($this->tags) && !empty($this->tags)){
					$formfields['tags'].=','.$this->tags(',');
				}
				$postData = http_build_query($formfields);
				print_r($postData);
				
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; rv:11.0) Gecko/20100101 Firefox/11.0');
    		 curl_setopt($ch, CURLOPT_HEADER ,1);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER ,1);
//				curl_setopt($ch, CURLOPT_FOLLOWLOCATION ,1);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
//				curl_setopt($ch, CURLOPT_USERPWD, $auth['user'] . ":" . $auth['password']);
//				curl_setopt($ch, CURLOPT_COOKIEJAR, $auth['cookies']);
//				curl_setopt($ch, CURLOPT_COOKIEFILE, $auth['cookies']);
				curl_setopt($ch, CURLOPT_POST,1);
				curl_setopt($ch, CURLOPT_POSTFIELDS,$postData);
				$result=curl_exec($ch);
				if (strpos($result, '302 Found') !== false){
					return true;
				} else {
    			warn(str_replace('%server', $url, loc('Sorry, I was not able to save event to %server.')));
				}
				
    	} else {
    		warn(str_replace('%server',$target_host,loc('Sorry, curl not callable. This means I am not allowed to send the event to %server.')));
    	}
    	return false;
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
    	
    	$yesterday=time()-24*60*60; //
    	$yesterday=date($db_time_format,$yesterday);
    
    	if ($tags!=null){
    		if (!is_array($tags)){
    			$tags=array($tags);
    		}
    		$sql="SELECT * FROM appointments NATURAL JOIN appointment_tags NATURAL JOIN tags WHERE end>'$yesterday' AND keyword IN (:tags) ORDER BY start";
    		if ($limit){
    			$sql.=' LIMIT :limit';
    		}
    		$stm=$db->prepare($sql);
    		$stm->bindValue(':tags', reset($tags));
    	} else {
    		$sql="SELECT * FROM appointments WHERE end>'$yesterday' ORDER BY start";
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
