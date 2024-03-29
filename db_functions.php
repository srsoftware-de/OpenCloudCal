<?php
/* this was written using http://code.tutsplus.com/tutorials/why-you-should-be-using-phps-pdo-for-database-access--net-12059 */
function connectToDb($host,$database,$user,$pass){
	try {
		$db = new PDO("mysql:host=$host;dbname=$database;charset=latin1", $user, $pass, array(PDO::ATTR_PERSISTENT => true)); // open db connection and cache it
		//      print "databse opened\n";
		return $db;
	} catch (PDOException $pdoex) {
		die($pdoex->getMessage());
	}
}

/* assures the existence of the config table */
function checkConfigTable($db){
	$results=$db->query("SHOW TABLES LIKE 'config'");
	if (!$results){
		die(print_r($dbh->errorInfo(), TRUE));
	}
	if ($results->rowCount()<1){
		//      echo "table doesn't exist\n";
		$sql = 'CREATE TABLE config (keyname VARCHAR(100) PRIMARY KEY, value TEXT NOT NULL);';
		$db->exec($sql);
		$sql = 'INSERT INTO config (keyname,value) VALUES ("dbversion","1")';
		$db->exec($sql);
		//    } else {
		//      echo "table exists\n";
	}
}

/* assures the existence of the urls table */
function checkUrlsTable($db){
	$results=$db->query("SHOW TABLES LIKE 'urls'");
	if (!$results){
		die(print_r($dbh->errorInfo(), TRUE));
	}
	if ($results->rowCount()<1){
		//      echo "table doesn't exist\n";
		$sql = 'CREATE TABLE urls (uid INT PRIMARY KEY AUTO_INCREMENT, url TEXT NOT NULL);';
		$db->exec($sql);
		//    } else {
		//      echo "table exists\n";
	}
}

/* assures the existence of the tags table */
function checkTagsTable($db){
	$results=$db->query("SHOW TABLES LIKE 'tags'");
	if (!$results){
		die(print_r($dbh->errorInfo(), TRUE));
	}
	if ($results->rowCount()<1){
		//      echo "table doesn't exist\n";
		$sql = 'CREATE TABLE tags (tid INT PRIMARY KEY AUTO_INCREMENT, keyword VARCHAR(100) NOT NULL);';
		$db->exec($sql);
		//    } else {
		//      echo "table exists\n";
	}
}

/* assures the existence of the sessions table */
function checkSessionsTable($db){
	$results=$db->query("SHOW TABLES LIKE 'sessions'");
	if (!$results){
		die(print_r($dbh->errorInfo(), TRUE));
	}
	if ($results->rowCount()<1){
		//      echo "table doesn't exist\n";
		$sql = 'CREATE TABLE sessions (sid INT PRIMARY KEY AUTO_INCREMENT, aid INT NOT NULL REFERENCES appointment(aid), description TEXT NOT NULL, start DATETIME NOT NULL, end DATETIME);';
		$db->exec($sql);
		//    } else {
		//      echo "table exists\n";
	}
}

/* assures the existence of the appointments table */
function checkAppointmentsTable($db){
	$results=$db->query("SHOW TABLES LIKE 'appointments'");
	if (!$results){
		die(print_r($dbh->errorInfo(), TRUE));
	}
	if ($results->rowCount()<1){
		//      echo "table doesn't exist\n";
		$sql = 'CREATE TABLE appointments (aid INT PRIMARY KEY AUTO_INCREMENT, title TEXT NOT NULL,description TEXT, start DATETIME NOT NULL, end DATETIME, location TEXT, coords TEXT);';
		$db->exec($sql);
		//    } else {
		//      echo "table exists\n";
	}
}

/* assures the existence of the appointment_urls table */
function checkAppointmentAttachmentsTable($db){
	$results=$db->query("SHOW TABLES LIKE 'appointment_attachments'");
	if (!$results){
		die(print_r($dbh->errorInfo(), TRUE));
	}
	if ($results->rowCount()<1){
		//      echo "table doesn't exist\n";
		$sql = 'CREATE TABLE appointment_attachments (aid INT NOT NULL REFERENCES appointments(aid),uid INT NOT NULL REFERENCES urls(uid), mime TEXT, PRIMARY KEY (aid,uid));';
		$db->exec($sql);
		//    } else {
		//      echo "table exists\n";
	}
}

/* assures the existence of the appointment_urls table */
function checkAppointmentUrlsTable($db){
	$results=$db->query("SHOW TABLES LIKE 'appointment_urls'");
	if (!$results){
		die(print_r($dbh->errorInfo(), TRUE));
	}
	if ($results->rowCount()<1){
		//      echo "table doesn't exist\n";
		$sql = 'CREATE TABLE appointment_urls (aid INT NOT NULL REFERENCES appointments(aid),uid INT NOT NULL REFERENCES urls(uid), description TEXT, PRIMARY KEY (aid,uid));';
		$db->exec($sql);
		//    } else {
		//      echo "table exists\n";
	}
}

/* assures the existence of the appointment_sessions table */
function checkAppointmentSessionsTable($db){
	$results=$db->query("SHOW TABLES LIKE 'appointment_sessions'");
	if (!$results){
		die(print_r($dbh->errorInfo(), TRUE));
	}
	if ($results->rowCount()<1){
		//      echo "table doesn't exist\n";
		$sql = 'CREATE TABLE appointment_sessions (aid INT NOT NULL REFERENCES appointments(aid),sid INT NOT NULL REFERENCES sessions(sid), PRIMARY KEY (aid,sid));';
		$db->exec($sql);
		//    } else {
		//      echo "table exists\n";
	}
}

/* assures the existence of the appointment_tags table */
function checkAppointmentTagsTable($db){
	$results=$db->query("SHOW TABLES LIKE 'appointment_tags'");
	if (!$results){
		die(print_r($dbh->errorInfo(), TRUE));
	}
	if ($results->rowCount()<1){
		//      echo "table doesn't exist\n";
		$sql = 'CREATE TABLE appointment_tags (aid INT NOT NULL REFERENCES appointments(aid),tid INT NOT NULL REFERENCES tags(tid), PRIMARY KEY (aid,tid));';
		$db->exec($sql);
		//    } else {
		//      echo "table exists\n";
	}
}

/* assures the existence of the appointment_tags table */
function checkImportedAppointmentsTable($db){
	$results=$db->query("SHOW TABLES LIKE 'imported_appointments'");
	if (!$results){
		die(print_r($dbh->errorInfo(), TRUE));
	}
	if ($results->rowCount()<1){
		//      echo "table doesn't exist\n";
		$sql = 'CREATE TABLE imported_appointments (md5hash BINARY(32) PRIMARY KEY, aid INT NOT NULL REFERENCES appointments(aid));';
		$db->exec($sql);
		//    } else {
		//      echo "table exists\n";
	}
}

/* assures the existence of all required database tables */
function checkTables($db){
	try {
		checkConfigTable($db);
		checkUrlsTable($db);
		checkTagsTable($db);
		checkAppointmentsTable($db);
		checkSessionsTable($db);
		checkAppointmentUrlsTable($db);
		checkAppointmentAttachmentsTable($db);
		checkAppointmentTagsTable($db);
		checkImportedAppointmentsTable($db);
	} catch (PDOException $pdoex){
		echo $pdoex->getMessage();
	}
}

function clear_imported($db){
	$stm=$db->prepare('SELECT aid FROM imported_appointments');
	$aids=array();
	if ($stm->execute()){
		$results=$stm->fetchAll();
		foreach ($results as $r){
			$aids[]=$r['aid'];
		}
	}
	$stm=$db->prepare('SELECT aid FROM appointment_tags NATURAL JOIN tags WHERE keyword = :key');
	if ($stm->execute(array(':key'=>loc('imported')))){
		$results=$stm->fetchAll();
		foreach ($results as $r){
			$aids[]=$r['aid'];
		}			
	}
	$aids=array_unique($aids,SORT_NUMERIC);
	sort($aids);
	$aid_string = implode(', ', $aids);
	
	$stm=$db->prepare('DELETE FROM appointment_tags WHERE aid IN ('.$aid_string.')');
	$stm->execute();
	
	$stm=$db->prepare('DELETE FROM appointment_attachments WHERE aid IN ('.$aid_string.')');
	$stm->execute();
	
	$stm=$db->prepare('DELETE FROM appointment_urls WHERE aid IN ('.$aid_string.')');
	$stm->execute();
	
	$stm=$db->prepare('DELETE FROM sessoins WHERE aid IN ('.$aid_string.')');
	$stm->execute();
	
	$stm=$db->prepare('DELETE FROM appointments WHERE aid IN ('.$aid_string.')');
	$stm->execute();
	
	$stm=$db->prepare('DELETE FROM appointments WHERE aid NOT IN (SELECT DISTINCT aid FROM appointment_tags)');
	$stm->execute(); // remove untagged appointments
	
	$stm=$db->prepare('DROP TABLE imported_appointments');
	$stm->execute();
}