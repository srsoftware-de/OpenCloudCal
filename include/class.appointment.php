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
      
      $c=explode(',',str_replace(' ', '', str_replace(';', ',', $coords)));
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
    	if (isset($this->tags)){
      	foreach ($this->tags as $tag){
    		  $result.='<a href="?tag='.$tag->text.'">'.$tag->text.'</a> '.PHP_EOL;
    	  }
    	}
    	return $result;    	 
    }
    
    function mapLink(){
    	if ($this->coords){
    		return 'http://www.openstreetmap.org/?mlat='.$this->coords['lat'].'&mlon='.$this->coords['lon'].'&zoom=15';
    	}
    	return false;
    }
    
    public static function readFromIcal(&$stack,$tag=null,$timezone=null){
    	$start=null;
    	$end=null;
    	$geo=null;
    	$urls=null;
    	$location=null;
    	$summary=null;
    	$description=null;
    	$foreignId=null;
    	$tags=array();
    	if ($tag!=null){
    		$tags[]=$tag;
    	}
  		while (!empty($stack)){			
  			$line=array_pop($stack);
			if (startsWith($line,' ')){
				continue;
			}
  			$line=trim($line);
			if (startsWith($line,'UID:')){
  				$foreignId=substr($line,4);
	  		} elseif (startsWith($line,'DTSTART:')){
  				$start=appointment::convertRFC2445DateTimeToUTCtimestamp(substr($line, 8),$timezone);
	  		} elseif (startsWith($line,'DTSTART;TZID=Europe/Berlin:')){
  				$start=appointment::convertRFC2445DateTimeToUTCtimestamp(substr($line, 27),$timezone);
	  		} elseif (startsWith($line,'CREATED:')){
	  		} elseif (startsWith($line,'SEQUENCE:')){
	  		} elseif (startsWith($line,'STATUS:')){
	  		} elseif (startsWith($line,'RRULE:')){
	  		} elseif (startsWith($line,'EXDATE')){
	  		} elseif (startsWith($line,'CONTACT')){
	  		} elseif (startsWith($line,'ATTENDEE')){
	  		} elseif (startsWith($line,'TRANSP:')){
	  		} elseif (startsWith($line,'LAST-MODIFIED:')){
	  		} elseif (startsWith($line,'DTSTART;VALUE=DATE:')){
  				$start=appointment::convertRFC2445DateTimeToUTCtimestamp(substr($line, 19).'T000000',$timezone);
	  		} elseif (startsWith($line,'DTEND:')){
  				$end=appointment::convertRFC2445DateTimeToUTCtimestamp(substr($line, 6), $timezone);
	  		} elseif (startsWith($line,'DTEND;TZID=Europe/Berlin:')){
  				$end=appointment::convertRFC2445DateTimeToUTCtimestamp(substr($line, 25), $timezone);
	  		} elseif (startsWith($line,'DTEND;VALUE=DATE:')){
  				$end=appointment::convertRFC2445DateTimeToUTCtimestamp(substr($line, 17).'T235959',$timezone);
	  		} elseif (startsWith($line,'GEO:')){
	  			$geo=str_replace('\;', ';',substr($line,4));
	  		} elseif (startsWith($line,'URL:')){
	  			if ($urls==null){
	  				$urls=array();
	  			}
	  			$urls[]=substr($line,4) . readMultilineFromIcal($stack);
	  		} elseif (startsWith($line,'LOCATION:')){
	  			$location=str_replace(array('\,','\n'), array(',',"\n"),substr($line,9) . readMultilineFromIcal($stack));
	  		} elseif (startsWith($line,'SUMMARY:')){
	  			$summary=str_replace(array('\,','\n'), array(',',"\n"),substr($line,8) . readMultilineFromIcal($stack));
	  		} elseif (startsWith($line,'CATEGORIES:')){
	  			$cats=str_replace(array('\,','\n'), array(',',"\n"),substr($line,11) . readMultilineFromIcal($stack));
	  			$cats=explode(',',$cats);
				$tags = array_merge($tags,$cats);
	  		} elseif (startsWith($line,'CATEGORIES;LANGUAGE=de-DE:')){
	  			$cats=str_replace(array('\,','\n'), array(',',"\n"),substr($line,26) . readMultilineFromIcal($stack));
	  			$cats=explode(',',$cats);
				$tags = array_merge($tags,$cats);
	  		} elseif (startsWith($line,'DESCRIPTION:')){
	  			$description=$line=str_replace(array('\,','\n'), array(',',"\n"), substr($line,12) . readMultilineFromIcal($stack));
	  		} elseif (startsWith($line,'CLASS:')){
	  			// no use for class at the moment
	  		} elseif (startsWith($line,'DTSTAMP:')){
	  			// no use for ststamp at the moment
	  		} elseif (startsWith($line,'X-')){
	  			// no use for ststamp at the moment
	  		} elseif ($line=='END:VEVENT'){
	  			// create appointment, do not save it, return it.
	  			if ($end==null){
	  				$end=$start;
	  			}
	  			$app=appointment::create($summary, $description, $start, $end, $location, $geo,false);
	  			$app->safeIfNotAlreadyImported($tags,$urls);
	  			
	  			return $app;
	  		} else {
  				warn('tag unknown to appointment::readFromIcal: '.$line);
  				return false;
  			}
  		}
  	}
  	
  	static function convertRFC2445DateTimeToUTCtimestamp($datetime,$timezone=null){
		global $db_time_format;
  		if (substr($datetime,-1)=='Z'){
  			$timezone='UTC';
  		}
  		$dummy=substr($datetime, 0,4).'-'.substr($datetime, 4,2).'-'.substr($datetime, 6,2).' '.	substr($datetime, 9,2).':'.substr($datetime, 11,2).':'.substr($datetime, 13,2);
			if ($timezone != null && $timezone != 'UTC'){
				if ($timezone['id']=='Europe/Berlin'){
					$_SESSION['country']='DE';
					$secs=strtotime($dummy);						
					$dummy=date($db_time_format,$secs-getTimezoneOffset($secs));
					
				} else {
					print_r($timezone);
					print $datetime;
					die();
					warn(str_replace('%tz', $timezone, loc('Handling of timezone "%tz" currently not implemented!')));
				}
			}
  		return $dummy;
  	}
  	
  	public function safeIfNotAlreadyImported($tags=null,$urls=null){
  		global $db;
  		if ($tags!=null && !empty($tags)){
  			if (in_array('OpenCloudCal', $tags)) return;
  			if (in_array('opencloudcal', $tags)) return;
  		}
  		$md5=md5($this->toVEvent(),TRUE);
  		$sql = 'SELECT aid FROM imported_appointments WHERE md5hash =:hash';
    	$stm=$db->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
    	$stm->execute(array(':hash'=>$md5));
    	$results=$stm->fetchAll();
    	if (count($results) < 1){
    		$this->save();    		
    		$this->addTag(loc('imported'));
    		if ($tags!=null && !empty($tags)){
	    		foreach ($tags as $tag){
  	  			$this->addTag(trim($tag));
    			}
    		}
    		if ($urls!=null && !empty($urls)){
    			foreach ($urls as $url){
    				$this->addUrl($url);
    			}
    		}    		
    		$sql = 'INSERT INTO imported_appointments (aid,md5hash) VALUES (:aid,:hash)';
    		$stm=$db->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
    		$stm->execute(array(':aid'=>$this->id,':hash'=>$md5));
    	} else {
    		$keys=array('%title','%id');
    		$values=array($this->title,$results[0]['aid']);
    		warn(str_replace($keys, $values, loc('"%title" already present (<a href="?show=%id">link</a>)!')));    		
    	}    		 
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
    	$this->attachments=$this->getAttachments();    	 
    	$this->urls				=$this->getUrls();
    	$this->tags				=$this->getTags();
    	$this->sessions		=session::loadAll($this->id);
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
    	
    	$sql = "DELETE FROM imported_appointments WHERE aid=:id";
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
				$url='https://calcifer.datenknoten.me/termine/';
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
				if (isset($this->urls)){
					if (count($this->urls)==1){ // if we only have one url: post the url directly
				  	$urls=$this->urls;
					  $date_url=reset($urls);
					  $formfields['url']=$date_url->address;
					}
					if (count($this->urls)>1){ // if we only several urls: link to the appointment in OpenCloudCal
						$formfields['url']='http'.(isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}?show=$this->id";
					}
				}
				$postData = http_build_query($formfields);
				
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; rv:11.0) Gecko/20100101 Firefox/11.0');
        curl_setopt($ch, CURLOPT_HEADER ,1);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER ,1);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION ,1);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
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
				$stm->execute(array(':uid' => $url->id,':aid' => $url->aid,':description'=>$url->description));
        $this->urls[$url->id]=$url;
      } else {
        $url=url::create($this->id, $url ,'Homepage');
        $url->save();
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
    /******** Attachments *****/
    
    /* adds an attachment to the appointment */
    function addAttachment($url){
    	global $db;
    	if ($url instanceof url){
    		$stm=$db->prepare("INSERT INTO appointment_attachments (uid,aid,mime) VALUES (:uid, :aid, :mime)", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
    		$stm->execute(array(':uid' => $url->id,':aid' => $url->aid,':mime'=>$url->description));
    		$this->urls[$url->id]=$url;
    	} else {
    		$url=url::create($this->id, $url ,'Attachment');
    		$url->save();
    		$this->addUrl($url);
    	}
    }
    
    function getAttachments(){
    	global $db;
    	$urls=array();
    	$sql="SELECT uid,mime FROM appointment_attachments WHERE aid=$this->id";
    	foreach ($db->query($sql) as $row){
    		$url=url::load($row['uid']);
    		if ($url){
    			$url->description=$row['mime'];
    			$urls[$url->id]=$url;
    		}
    	}
    	return $urls;
    }
    
    /* remove attachment from appointment */
    function removeAttachment($url){
    	global $db;
    	if ($url instanceof url){
    		$sql="DELETE FROM appointment_attachments WHERE uid=$url->id AND aid=$this->id";
    		$db->query($sql);
    		unset($this->urls[$url->id]);
    	} else {
    		if (is_int($url)){
    			$this->removeAttachment(url::load($url));
    		} else {
    			$this->removeAttachment(url::create($url));
    		}
    	}
    }
    /******** Attachments *****/
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
    
    function toVEvent(){	
    	$nl="\r\n";
      $result ='BEGIN:VEVENT'.$nl;
      if (isset($this->id) && $this->id != null) {
    		$result.='UID:'.$this->id.'@'.$_SERVER['HTTP_HOST'].$nl;
      }
    	$result.='DTSTART:'.str_replace(array('-',' ',':'),array('','T',''),$this->start).'Z'.$nl;
    	if (isset($this->tags) && $this->tags != null){
    		$result.='CATEGORIES:'.$this->tags(',').$nl;
    	}
    	$result.='CLASS:PUBLIC'.$nl;
    	$result.=wordwrap('DESCRIPTION:'.str_replace("\r\n","\\n",$this->description),75,"\r\n ").$nl;
    	$result.='DTSTAMP:'.str_replace(array('-',' ',':'),array('','T',''),$this->start).'Z'.$nl;
    	$result.='GEO:'.$this->coords['lat'].';'.$this->coords['lon'].$nl;
    	$result.='LOCATION:'.$this->location.$nl;
    	$result.='SUMMARY:'.$this->title.$nl;
    	if (isset($this->id) && $this->id != null){ 
    		$result.='URL:http://'.$_SERVER['HTTP_HOST'].$_SERVER["PHP_SELF"].'?show='.$this->id.$nl;
    	}   
	if (isset($this->attachments) && is_array($this->attachments)){
	    	foreach ($this->attachments as $attachment){
    			$result.='ATTACH;FMTTYPE='.$attachment->description.':'.$attachment->address.$nl;
		}
    	}
    	$result.='DTEND:'.str_replace(array('-',' ',':'),array('','T',''),$this->end).'Z'.$nl;
    	$result.='END:VEVENT'.$nl;
    	return $result;    	    	
    }
  }
?>
