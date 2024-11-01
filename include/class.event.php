<?php

class Event {

	/* create new appointment object */
	function __construct(){
		$this->id=NULL;
		$this->title=NULL;
		$this->description=NULL;
		$this->start=NULL;
		$this->end=NULL;
		$this->location=NULL;
		$this->coords=NULL;
		$this->tags=array();
		$this->links=array();
		$this->attachments=array();
		$this->import_src_url_hash=NULL;
	}

	/** start and end are expected to be UTC timestamps in the form YYYY-MM-DD hh:mm:ss **/
	public static function create($title, $description, $start, $end=null, $location=null, $coords=null, $tags=null, $links=null, $attachments=null,$save=true){

		$instance=new self();
		$instance->title=$title;
		$instance->description=$description;
		$instance->start=$start;
		$instance->end=$end;
		$instance->location=$location;

		$instance->set_coords($coords);
		//print_r(['Before Creation'=>$tags]);
		if ($tags!=null){
			if (!is_array($tags)) $tags=explode(' ', $tags);
			foreach ($tags as $tag) $instance->add_tag($tag);
			//print_r(['During Creation'=>$tags]);
		}
		if ($links!=null){
			foreach ($links as $link) $instance->add_link($link);
		}
		if ($attachments!=null){
			foreach ($attachments as $attachment) $instance->add_attachment($attachment);
		}
		if ($save) $instance->save();
		//print_r(['Post Creation'=>$tags]);
		return $instance;
	}

	public static function get_imported($import_src_url){
		global $db;
		$url_hash=md5($import_src_url);
		$sql = 'SELECT aid FROM imported_appointments WHERE md5hash =:hash';
		$stm=$db->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		$stm->execute(array(':hash'=>$url_hash));
		$results=$stm->fetchAll();
		if (count($results) < 1){ // not imported, yet
			return null;
		} else { // already imported
			$aid=$results[0]['aid'];
			$appointment = Event::load($aid);
			return $appointment;
		}
	}

	/** sets the coordinates of this event **/
	function set_coords($coords){
		if ($coords==NULL){
			$this->coords=NULL;
		} elseif (is_array($coords)){
			$this->coords=$coords;
		} else { // if coords given as string => convert to array
			$c=explode(',',str_replace(' ', '', str_replace(';', ',', $coords)));
			$c=explode(',',str_replace(' ', '', str_replace(';', ',', $coords)));
			if (count($c)==2){
				$this->coords=array('lat'=>$c[0],'lon'=>$c[1]);
			} else {
				$this->coords=NULL;
			}
		}
	}

	function set_title($title){
		$this->title=$title;
	}

	function set_description($description){
		$this->description=$description;
	}

	function set_start($start){
		if ($this->end == $this->start){
			$this->end=$start;
		}
		$this->start=$start;
	}

	function set_end($end){
		$this->end=$end;
	}

	function set_location($loc){
		$this->location=$loc;
	}
	/** create links for tags of this event **/
	function tagLinks(){
		$result="";
		if (isset($this->tags)){
			foreach ($this->tags as $tag){
				$result.='<a href="?tag='.$tag->text.'">'.$tag->text.'</a> '.PHP_EOL;
			}
		}
		return $result;
	}

	/** create map link for this event **/
	function mapLink(){
		if ($this->coords){
			return 'http://www.openstreetmap.org/?mlat='.$this->coords['lat'].'&mlon='.$this->coords['lon'].'&zoom=15';
		}
		return false;
	}
	
	public static function parseDate($line,$timezone){
	    $parts = explode(':',$line);
	    $tag = $parts[0];
	    $val = $parts[1];
	    $mods = explode(';', $tag);
	    array_shift($mods);
	    foreach ($mods as $mod){
	        $mod_parts = explode('=', $mod);
	        $mod_key = $mod_parts[0];
	        $mod_val = $mod_parts[1];
	        switch ($mod_key){
	            case 'TZID': $timezone = $mod_val; break;
	            case 'VALUE':
	                if ($mod_val == 'DATE') $val .= 'T000000';
	                break;
	        }
	    }
	    return Event::convertRFC2445DateTimeToUTCtimestamp($val,$timezone);
	}

	/** read an event from an ical file **/
	public static function readFromIcal(&$stack,$tags=null,$timezone=null,$source_url=null){
		$start=null;
		$end=null;
		$geo=null;
		$links=null;
		$location=null;
		$summary=null;
		$description=null;
		$foreignId=null;
		$attachments=null;
		if ($tags==null) $tags=[];
		if (!is_array($tags)) $tags=[$tags];
		while (!empty($stack)){
			$line=array_pop($stack);
			if (startsWith($line,' '))continue;
			$line=trim($line);
			if (startsWith($line,'UID:')){
				$foreignId=substr($line,4).readMultilineFromIcal($stack);
			} elseif (startsWith($line,'DTSTART')){
			    $start = self::parseDate($line,$timezone);
			} elseif (startsWith($line,'DTEND')){
			    $end = self::parseDate($line,$timezone);
			} elseif (startsWith($line,'CREATED:')){
			} elseif (startsWith($line,'SEQUENCE:')){
			} elseif (startsWith($line,'STATUS:')){
			} elseif (startsWith($line,'RRULE:')){
			} elseif (startsWith($line,'EXDATE')){
			} elseif (startsWith($line,'CONTACT')){
			} elseif (startsWith($line,'ORGANIZER')){
			} elseif (startsWith($line,'ATTENDEE')){
			} elseif (startsWith($line,'TRANSP:')){
			} elseif (startsWith($line,'LAST-MODIFIED:')){
			} elseif (startsWith($line,'GEO:')){
				$geo=str_replace('\;', ';',substr($line,4));
			} elseif (startsWith($line,'URL')){
				if ($links==null) $links=[];
				$links[]=Event::readUrl(substr($line,3) . readMultilineFromIcal($stack));
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
				//$cats=explode(',',$cats);
				$cats=preg_split('/(,|\/)/', $cats);
				$tags = array_merge($tags,$cats);
			} elseif (startsWith($line,'DESCRIPTION:')){
				$description=$line=str_replace(array('\,','\n'), array(',',"\n"), substr($line,12) . readMultilineFromIcal($stack));
			} elseif (startsWith($line,'CLASS:')){
				// no use for class at the moment
			} elseif (startsWith($line,'DTSTAMP')){
				// no use for ststamp at the moment
			} elseif (startsWith($line,'RECURRENCE-ID')){
				// no use for ststamp at the moment
			} elseif (startsWith($line,'X-WP-IMAGES-URL:')){
				$raw = str_replace(array('\,','\n'), array(',',"\n"), substr($line,16) . readMultilineFromIcal($stack));
				$pos1 = strpos($raw,'http');
				$pos2 = strpos($raw,'jpg');
				$address=substr($raw,$pos1,$pos2-$pos1+3);
				if (!empty($address)){
					$mime = guess_mime_type($address);
					$url = url::create($address,$mime);
					$attachments[]=$url;
				}

				// no use for ststamp at the moment
			} elseif (startsWith($line,'X-')){
				// no use for ststamp at the moment
			} elseif (startsWith($line, 'ATTACH;FMTTYPE=image')){
				$pos=strpos($line, ':');
				$address=substr($line, $pos+1);
				if (!empty($address)){
					$mime = guess_mime_type($address);
					$url = url::create($address,$mime);
					$attachments[]=$url;
				}
			} elseif (startsWith($line, 'ATTACH:http')){
				$pos=strpos($line, ':');
				$address=substr($line, $pos+1);
				if (!empty($address)){
					$pos=strpos($address,' ');
					$attachment_description=null;
					if ($pos !== false){
						$attachment_description = substr($address,$pos+1);
						$address=substr($address,0,$pos);
					}
					$url = url::create($address,$attachment_description);
					$links[]=$url;
				}
			} elseif ($line=='END:VEVENT'){
				// create appointment, do not save it, return it.
				if (in_array('opencloudcal', $tags)) return null; // do not re-import events

				if ($foreignId != null){
					if (startsWith($foreignId, 'http')){
						$id=$foreignId;
					} else {
						$id=$source_url.'#'.$foreignId;
					}
				} else {
					$id=$source_url;
				}
				$app = Event::get_imported($id);
				if ($app != null){
				    $app->set_title($summary);
					$app->set_description($description);
					$app->set_start($start);
					$app->set_end($end);
					$app->set_location($location);
					$app->set_coords($geo);
					if (is_array($tags)){
						foreach ($tags as $tag){
							$app->add_tag($tag);
						}
					}
					if (is_array($links)){
						foreach ($links as $link){
							$app->add_link($link);
						}
					}
					if (is_array($attachments)){
						foreach ($attachments as $attachment){
							$app->add_attachment($attachment);
						}
					}
				} else {
					$app=Event::create($summary, $description, $start, $end, $location, $geo,$tags,$links,$attachments,false);
				}
				$app->mark_imported($id);
				return $app;
			} else {
				warn('tag unknown to Event::readFromIcal: '.$line);
			}
		}
	}

	static function readUrl($line){
		while (startsWith($line, ';')){
			if (startsWith($line, ';VALUE=URI')){
				$line = substr($line,10);
			}
		}
		if (startsWith($line, ':')) $line = substr($line, 1);
		return $line;
	}


	static function readTZID(&$timestring){
		if (startsWith($timestring, '"')){
			$pos = strpos($timestring, '"',1);
			$tzid = substr($timestring,1,$pos-1);
			$timestring = substr($timestring, $pos+1);
		} else {
			$pos = strpos($timestring, ':',1);
			$tzid = substr($timestring,	0,$pos);
			$timestring = substr($timestring, $pos);
		}
		if (startsWith($tzid, '+')){
			$offset = explode(':', substr($tzid,1));
			return ['id'=>'offset','offset'=>array('h'=>(int)$offset[0],'m'=>(int)$offset[1])];
		}
		return ['id'=>$tzid];
	}

	/** convert an RFC 2445 formatted time string to a UTC timestamp **/
	static function convertRFC2445DateTimeToUTCtimestamp($timestring,$timezone=null){
		if (substr($timestring,-1)=='Z') $timezone='UTC';
		while (startsWith($timestring, ';')){
			if (startsWith($timestring, ';TZID=')){
				$timestring = substr($timestring, 6);
				$tzid = self::readTZID($timestring);
				if (!is_array($timezone) || $timezone['id']!=$tzid['id']){
					if (in_array($tzid['id'],['Europe/Berlin','offset'])){
						$timezone = $tzid;
					} else warn(str_replace('%tz', print_r($tzid,true), loc('Handling of timezone "%tz" currently not implemented!')));
				}
			}
		}
		if (startsWith($timestring, ':'))$timestring = substr($timestring, 1);
		$dummy=substr($timestring, 0,4).'-'.substr($timestring, 4,2).'-'.substr($timestring, 6,2).' '.	substr($timestring, 9,2).':'.substr($timestring, 11,2).':'.substr($timestring, 13,2);

		if ($timezone != null && $timezone != 'UTC'){
			if (isset($timezone['offset'])){
				$secs=strtotime($dummy);
				$offset = 3600*$timezone['offset']['h']+60*$timezone['offset']['m'];
				$dummy=date(TIME_FMT,$secs-$offset);
			} elseif (isset($timezone['id'])){
				if ($timezone['id']=='Europe/Berlin'){
					$_SESSION['country']='DE';
					$secs=strtotime($dummy);
					$dummy=date(TIME_FMT,$secs-getTimezoneOffset($secs));
				} else {
					warn(str_replace('%tz', print_r($timezone,true), loc('Handling of timezone "%tz" currently not implemented!')));
				}
			} else {
				warn(str_replace('%tz', print_r($timezone,true), loc('Handling of timezone "%tz" currently not implemented!')));
			}
		}

		return $dummy;
	}

	/* adds a hash based on the import url to the event and saves the event */
	function mark_imported($import_src_url){
		global $db;
		if (!isset($import_src_url) || $import_src_url==null || empty($import_src_url)){
			return;
		}
		$this->import_src_url_hash=md5($import_src_url);
		$this->save();
	}

	/* loads tags, urls and sessions related to the current appointment */
	function loadRelated(){
		$this->attachments			= $this->get_attachments();
		$this->links	  			= $this->get_links();
		$this->tags		  			= $this->get_tags();
		$this->import_src_url_hash	= $this->get_import_src_url_hash();
		$this->sessions				= session::loadAll($this->id);
	}

	static function delete($id=false){
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
		$this->auto_add_tags();
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
		$this->save_tags();
		$this->save_links();
		$this->save_attachments();
		
		if ($this->import_src_url_hash != null){
			$sql = 'INSERT INTO imported_appointments (aid,md5hash) VALUES (:aid,:hash)';
			$stm=$db->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
			$stm->execute(array(':aid'=>$this->id,':hash'=>$this->import_src_url_hash));
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
			if (isset($this->links) && !empty($this->links)){
				foreach ($this->links as $url){
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
			if (isset($this->links)){
				if (count($this->links)==1){ // if we only have one url: post the url directly
					$links=$this->links;
					$date_url=reset($links);
					$formfields['url']=$date_url->address;
				}
				if (count($this->links)>1){ // if we only several urls: link to the appointment in OpenCloudCal
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

	function get_tags(){
		global $db;
		$tags=array();
		$sql="SELECT tid FROM appointment_tags WHERE aid=$this->id";
		foreach ($db->query($sql) as $row){
			$tag=tag::load($row['tid']);
			$tags[strtoupper($tag->text)]=$tag;
		}
		return $tags;
	}

	/* Adds a tag to the appointment. While the tag is instantly created in the database,
	 * the assignment will not be saved before $this->save() is called. */
	function add_tag($tag){
		if ($tag === null) return;
		if ($tag instanceof tag){
			$this->tags[strtoupper($tag->text)]=$tag;
		} else {
			$this->add_tag(tag::create($tag));
		}
	}

	/* save all tags belonging to event */
	private function save_tags(){
		global $db;
		foreach ($this->tags as $tag){
			$sql="INSERT IGNORE INTO appointment_tags (tid,aid) VALUES ($tag->id, $this->id)";
			$db->query($sql);
		}
	}

	function expand_tags($keyword,$tags){
		$keyword = strtoupper($keyword);
		//print_r(['expand'=>['key'=>$keyword,'tags'=>$tags]]);
		if (array_key_exists($keyword, $this->tags)){
			//print_r(['Match'=>$keyword]);
			if (is_array($tags)){
				foreach ($tags as $tag) $this->add_tag($tag);
			} else $this->add_tag($tags);
		}
	}

	function auto_add_tags(){
		// these tags are accepted in both title and description
		$description_tags = array(
				'50s'=>'50er',
				'70er'=>'70er',
				'70s'=>'70er',
				'70´er'=>'70er',
				'80er'=>'80er',
				'80s'=>'80er',
				'80´er'=>'80er',
				'90er'=>'90er',
				'90s'=>'90er',
				'90´er'=>'90er',
				'AC/DC'=>'Metal',
				'Alternative-Rock'=>'AlternativeRock',
				'Alrauna'=>'Alrauna',
				'Ausstellung'=>'Ausstellung',
				'Bad Taste'=>'BadTaste',
				'Balkan'=>'Balkan',
				'Black Channel'=>'Gothic',
		        'Black Metal'=>'BlackMetal',
				'Blues'=>'Blues',
				'Brettspiele'=>'Brettspiele',
				'Charts'=>'Charts',
				'Cosmic Dawn'=>'CosmicDawn',
				'Country'=>'Country',
				'Coverband'=>'Konzert',
				'DANCEHALL'=>'Dancehall',
				'Dark Electro'=>'DarkElectro',
				'Dark Side'=>'Gothic',
				'Dark Wave'=>'DarkWave',
				'Darkwave'=>'DarkWave',
				'Death-Metal'=>'DeathMetal',
				'Depeche Mode' => 'Wave',
				'Deutsch Rock'=>'Deutschrock',
				'Deutschrock'=>'Deutschrock',
				'Dia Show'=>'DiaShow',
				'Disco'=>'Disco',
				'Disko'=>'Disco',
				'Doom'=>'Doom',
				'Drschz Bäm'=>'Jazz',
				'Elektropop'=>'Elektropop',
				'Elektro-Pop'=>'Elektropop',
				'Film'=>'Kino',
				'Folkrock'=>'Folkrock',
				'Funk'=>'Funk',
				'Fußball'=>'Fußball',
				'Fussball'=>'Fußball',
				'Futurepop'=>'Futurepop',
				'Future pop'=>'Futurepop',
				'Gothrock'=>'GothRock',
				'Gothic'=>'Gothic',
				'Halloween' => 'Halloween',
				'HardRock'=>'HardRock',
				'Hard-Rock'=>'HardRock',
				'HipHop'=>'HipHop',
				'Hip-Hop'=>'HipHop',
				'Humppa'=>'Humppa',
				'Jazz'=>'Jazz',
				'Karneval'=>'Karneval',
				'Kammermusik'=>'Kammermusik',
				'Kino'=>'Kino',
				'Kirsche & Co'=>'Konzert',
				'Konzert'=>'Konzert',
				'Knorkator'=>'Metal',
				'Konzert'=>'Konzert',
				'Lesebühne'=>'Lesung',
				'Lesung '=>'Lesung',
				'Liedermach'=>'Liedermacher',
				'Linux'=>'Linux',
				'Markt'=>'Markt',
				'Med-Club'=>'MedClub',
				'Metal '=>'Metal',
				'Modellbahn'=>'Modellbahn',
				' NDH'=>'NDH',
				'Open Air'=>'OpenAir',
				'Party'=>'Party',
				'Polka'=>'Polka',
				'progressive'=>'Progressive',
				'psychedelic'=>'Psychedelic',
				'Punk '=>'Punkrock',
				'Punkrock'=>'Punkrock',
				'Punks '=>'Punkrock',
				'Rap '=>'Rap',
				' RNB' =>'RnB',
				'Rock'=>'Rock',
				'Rock’n Roll'=>'RockNRoll',
				'Rock´n Roll'=>'RockNRoll',
				'Rock´n´Roll'=>'RockNRoll',
				"Rock'n'Roll"=>'RockNRoll',
				'Rock ’n‘ Roll'=>'RockNRoll',
				'Rollenspiel'=>'Rollenspiel',
				'schattentanz'=>'schwarzesjena',
				'schwarzer tanz'=>'Gothic',
				'Seminar'=>'Seminar',
				'Songwriter'=>'Liedermacher',
				'Southern Rock'=>'Southern',
				'Stoner'=>'Stoner',
				'Software'=>'Software',
				'Syntension'=>'ProgressiveMetal',
				'Synthie Pop'=>'Synthpop',
				'Tanz'=>'Tanzen',
				'Techno'=>'Techno',
				'Theater'=>'Theater',
				'Windows'=>'Windows',
				'Volleyball'=>'Volleyball',
				'Universität'=>'Universität',
				'Zirkus'=>'Zirkus',
		);

		// these tags are only accepted in the title
		$title_tags=[
			'Festival'=>'Festival',
		];

		//print_r(['Before'=>$this->tags]);
		$text = preg_replace("/[^\wÄäÖöÜüß]+/", ' ', strip_tags(" $this->title $this->description "));
		foreach ($description_tags as $keyword => $tag){
			if (stripos($text,$keyword) !== false) $this->add_tag($tag);
		}

		$text = preg_replace("/[^\wÄäÖöÜüß]+/", ' ', strip_tags(" $this->title "));
		foreach ($title_tags as $keyword => $tag){
			if (stripos($text,$keyword) !== false) $this->add_tag($tag);
		}


		$expand_tags = array(
				'AlternativeRock'=>'Rock',
				'Alrauna'=>'FolkMetal',
				'Batcave'=>'schwarzesjena',
				'BlackMetal'=>'Metal',
				'DarkAmbient'=>'schwarzesjena',
				'Dark-Calssics'=>'schwarzesjena',
				'Dark-Electro'=>'schwarzesjena',
				'DarkMetal'=>'Metal',
				'DarkWave'=>'schwarzesjena',
				'DeathMetal'=>'Metal',
				'Discotheque'=>'Disco',
				'Doom'=>'schwarzesjena',
				'DoomMetal'=>'Metal',
				'EBM'=>'schwarzesjena',
				'Fußball'=>'Sport',
				'Goth'=>'schwarzesjena',
				'Gothic'=>'schwarzesjena',
				'GothicMetal'=>['schwarzesjena','Gothic','Metal'],
				'GothMetal'=>['schwarzesjena','Gothic','Metal'],
				'GothicRock'=>['schwarzesjena','Gothic','Rock'],
				'GothRock'=>['schwarzesjena','Gothic','Rock'],
				'Hardrock'=>'Rock',
				'HeavyMetal'=>'Metal',
				'OpenAir'=>'Festival',
				'Festival'=>'Konzert',
				'FolkMetal'=>['Folk','Metal'],
				'Folkrock'=>['Folk','Rock'],
				'Fussball'=>['Sport','Fußball'],
				'Fußball'=>['Sport'],
				'HardRock'=>'Rock',
				'Industrial'=>'schwarzesjena',
				'Karneval'=>'Fasching',
				'Liedermacher'=>'Konzert',
				'Konzert'=>'Live',
				'MelodicDeathMetal'=>['Metal','DeathMetal'],
				'NDH'=>'schwarzesjena',
				'Powermetal'=>'Metal',
				'ProgressiveMetal'=>['Metal','Progressive'],
				'Punkrock'=>['Rock','Punk'],
				'Rap'=>'HipHop',
				'RockNRoll'=>'Rock',
				'Stoner'=>'Rock',
				'Symphonicmetal'=>'Metal',
				'VikingMetal'=>'Metal',
				'Volleyball'=>'Sport',
				'Wave'=>'schwarzesjena',
				'Metal'=>'schwarzesjena',

		);

		//print_r(['Between'=>$this->tags]);
		foreach ($expand_tags as $keyword => $tags){
			$this->expand_tags($keyword,$tags);
		}
		//print_r(['After'=>$this->tags]);
		if (array_key_exists('LESUNG', $this->tags)){
			unset($this->tags['KONZERT']);
		}
		if (array_key_exists('KONZERT', $this->tags)){
			unset($this->tags['KINO']);
		}

	}

	/* remove tag from appointment */
	function remove_tag($tag){
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

	function remove_all_tags(){
		global $db;
		$sql="DELETE FROM appointment_tags WHERE aid=$this->id";
		$db->query($sql);
	}

	/****** TAGS **************/
	/****** LINKS **************/

	function get_links(){
		global $db;
		$links=array();
		$sql="SELECT uid,description FROM appointment_urls WHERE aid=$this->id";
		foreach ($db->query($sql) as $row){
			$url=url::load($row['uid']);
			if ($url){
				$url->description=$row['description'];
				$links[$url->id]=$url;
			}
		}
		return $links;
	}

	/* Adds a url to the appointment. Both the URL and the assignment between event and URL will not be saved
	 * before the appointment is saved. */
	function add_link($link){
		if ($link instanceof url){
			$this->links[]=$link;
		} else {
			$link=url::create($link ,'Homepage');
			$this->add_link($link);
		}
	}

	/* saves all links of the appointment */
	private function save_links(){
		global $db;
		foreach ($this->links as $url){
			if ($url->save()){
				$stm=$db->prepare("INSERT IGNORE INTO appointment_urls (uid,aid,description) VALUES (:uid, :aid, :description)", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
				$stm->execute(array(':uid' => $url->id,':aid' => $this->id,':description'=>$url->description));
			}
		}
	}

	/* remove url from appointment */
	function remove_link($link){
		global $db;
		if ($link instanceof url){
			$sql="DELETE FROM appointment_urls WHERE uid=$link->id AND aid=$this->id";
			$db->query($sql);
			unset($this->links[$link->id]); // TODO will not work in this way
		} else {
			if (is_int($link)){
				$this->remove_link(url::load($link));
			} else {
				$this->remove_link(url::create($link));
			}
		}
	}

	/********* LINKS ***********/
	/******** Attachments *****/

	/* adds an attachment to the appointment */
	function add_attachment($attachment){
		global $db;
		if ($attachment instanceof url){
			$this->attachments[]=$attachment;
		} else {
			$attachment=url::create($attachment ,'Attachment');
			$this->add_link($attachment);
		}
	}

	function get_attachments(){
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
	function remove_attachment($url){
		global $db;
		if ($url instanceof url){
			$sql="DELETE FROM appointment_attachments WHERE uid=$url->id AND aid=$this->id";
			$db->query($sql);
			unset($this->links[$url->id]);
		} else {
			if (is_int($url)){
				$this->remove_attachment(url::load($url));
			} else {
				$this->remove_attachment(url::create($url));
			}
		}
	}

	/* saves all links of the appointment */
	private function save_attachments(){
		global $db;
		foreach ($this->attachments as $url){
			$url->save();
			$stm=$db->prepare("INSERT IGNORE INTO appointment_attachments (uid,aid,mime) VALUES (:uid, :aid, :mime)", array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
			$stm->execute(array(':uid' => $url->id,':aid' => $this->id,':mime'=>$url->description));
		}
	}
	/******************...Attachments */

	public static function load($id){
		global $db;
		$instance=new self();
		$sql="SELECT * FROM appointments WHERE appointments.aid=$id";
		foreach ($db->query($sql) as $row){
			$instance=self::create($row['title'], $row['description'], $row['start'], $row['end'], $row['location'],$row['coords'],null,null,null,false);
			$instance->id=$id;
			$instance->loadRelated();
			return $instance;
		}
	}

	/* loading all appointments */
	public static function loadAll($tags=null){
		global $db,$limit;
		$appointments=array();

		if ($tags!=null){
			if (!is_array($tags)){
				$tags=array($tags);
			}
			$sql="SELECT * FROM appointments NATURAL JOIN appointment_tags NATURAL JOIN tags WHERE keyword IN (:tags) ORDER BY start";
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
			$appointment=self::create($row['title'], $row['description'], $row['start'], $row['end'], $row['location'],$row['coords'],null,null,null,false	);
			$appointment->id=$row['aid'];
			$appointment->loadRelated();
			$appointments[$appointment->id]=$appointment;
		}
		return $appointments;
	}

	public static function loadCurrent($tags=null,$all_keywords=true){
		global $db,$limit;
		$appointments=array();

		$yesterday=time()-12*60*60; // show events in the past 12 hours, too
		$yesterday=date(TIME_FMT,$yesterday);

		if ($tags!=null){
			if (!is_array($tags)){
				$tags=array($tags);
			}
			$sql="SELECT *
				  FROM appointments NATURAL JOIN appointment_tags NATURAL JOIN tags
				  WHERE (start>'$yesterday' OR end>'$yesterday')
				  AND keyword IN (";
			$sql.=':tag'.implode(', :tag',array_keys($tags));
			/* array( [0] => a, [1] => b, [2] => c ) wird zu ':tag'.0.', :tag'.1.', :tag'.2 = ':tag0, :tag1, :tag2' */
			$sql.=')
				  GROUP BY aid';
			if ($all_keywords){
				$sql.=' HAVING COUNT(DISTINCT tid) = :count ';
			}
			$sql.='ORDER BY start';
			if ($limit){
				$sql.=' LIMIT :limit';
			}
			error_log($sql);
			$stm=$db->prepare($sql);
			foreach ($tags as $key => $tag){
				$stm->bindValue(':tag'.$key, $tag);
			}
			if ($all_keywords){
				$stm->bindValue(':count', count($tags));
			}
		} else {
			$sql="SELECT * FROM appointments WHERE start>'$yesterday' OR end>'$yesterday' ORDER BY start";
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
			$appointment=self::create($row['title'], $row['description'], $row['start'], $row['end'], $row['location'],$row['coords'],null,null,null,false	);
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

	// appends link title as get parameter to url
	function urlWithTitle($url){
		$address=$url->address;
		if (empty($url->description)) return $address;
		if (strpos($address, '#') !==false){
			return replace_spaces($address.','.$url->description);
		}
		return replace_spaces($address.'#'.$url->description);
	}

	function toVEvent(){
		$result = icalLine('BEGIN','VEVENT');
		if (isset($this->id) && $this->id != null) {
			$result.=icalLine('UID',$this->id.'@'.$_SERVER['HTTP_HOST']);
		}
		$result.=icalLine('DTSTART',str_replace(array('-',' ',':'),array('','T',''),$this->start).'Z');
		if (isset($this->tags) && $this->tags != null){
			$result.=icalLine('CATEGORIES',$this->tags(','));
		}
		$result.=icalLine('CLASS','PUBLIC');
		$result.=icalLine('DESCRIPTION',$this->description);
		$result.=icalLine('DTSTAMP',str_replace(array('-',' ',':'),array('','T',''),$this->start).'Z');
		if ($this->coords !=null){
			$result.=icalLine('GEO',$this->coords['lat'].';'.$this->coords['lon']);
		}
		$result.=icalLine('LOCATION',$this->location);
		$result.=icalLine('SUMMARY',$this->title);
		if (isset($this->id) && $this->id != null){
			$result.=icalLine('URL','http://'.$_SERVER['HTTP_HOST'].$_SERVER["PHP_SELF"].'?show='.$this->id);
		}
		if (isset($this->attachments) && is_array($this->attachments)){
			foreach ($this->attachments as $attachment){
				$result.=icalLine('ATTACH;FMTTYPE='.$attachment->description,replace_spaces($attachment->address));
			}
		}
		if (isset($this->links) && is_array($this->links)){
			foreach ($this->links as $link){
				if ($link instanceof url){
					$result.=icalLine('ATTACH',$this->urlWithTitle($link));
				} else {
					$result.=icalLine('ATTACH',replace_spaces($link));
				}
			}
		}
		if ($this->end != null){
			$result.=icalLine('DTEND',str_replace(array('-',' ',':'),array('','T',''),$this->end).'Z');
		}
		$result.=icalLine('END','VEVENT');
		return $result;


	}

	function get_import_src_url_hash(){
		global $db;
		$hash = null;
		$sql="SELECT md5hash FROM imported_appointments WHERE aid=$this->id";
		foreach ($db->query($sql) as $row) $hash = $row['md5hash'];
		return $hash;
	}
}
?>
