<?php

  require 'config/db.php';

  /* this was written using http://code.tutsplus.com/tutorials/why-you-should-be-using-phps-pdo-for-database-access--net-12059 */
  function connectToDb($host,$database,$user,$pass){
    try {
      $db = new PDO("mysql:host=$host;dbname=$database", $user, $pass, array(PDO::ATTR_PERSISTENT => true)); // open db connection and cache it
      print "databse opened\n";
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
      echo "table doesn't exist\n";
      $sql = 'CREATE TABLE config (keyname VARCHAR(100) PRIMARY KEY, value TEXT NOT NULL);';      
      $db->exec($sql);
      $sql = 'INSERT INTO config (keyname,value) VALUES ("dbversion","1")';
      $db->exec($sql);      
    } else {
      echo "table exists\n";
    }
  }
  
  /* assures the existence of the urls table */
  function checkUrlsTable($db){
  	$results=$db->query("SHOW TABLES LIKE 'urls'");
  	if (!$results){
  		die(print_r($dbh->errorInfo(), TRUE));
  	}
  	if ($results->rowCount()<1){
  		echo "table doesn't exist\n";
  		$sql = 'CREATE TABLE urls (uid INT PRIMARY KEY AUTO_INCREMENT, url TEXT NOT NULL);';
  		$db->exec($sql);
  	} else {
  		echo "table exists\n";
  	}
  }

  /* assures the existence of the tags table */
  function checktagsTable($db){
  	$results=$db->query("SHOW TABLES LIKE 'tags'");
  	if (!$results){
  		die(print_r($dbh->errorInfo(), TRUE));
  	}
  	if ($results->rowCount()<1){
  		echo "table doesn't exist\n";
  		$sql = 'CREATE TABLE tags (tid INT PRIMARY KEY AUTO_INCREMENT, keyword TEXT NOT NULL);';
  		$db->exec($sql);
  	} else {
  		echo "table exists\n";
  	}
  }
  
  /* assures the existence of the sessions table */
  function checksessionsTable($db){
  	$results=$db->query("SHOW TABLES LIKE 'sessions'");
  	if (!$results){
  		die(print_r($dbh->errorInfo(), TRUE));
  	}
  	if ($results->rowCount()<1){
  		echo "table doesn't exist\n";
  		$sql = 'CREATE TABLE sessions (sid INT PRIMARY KEY AUTO_INCREMENT, description TEXT NOT NULL, start DATETIME NOT NULL, end DATETIME);';
  		$db->exec($sql);
  	} else {
  		echo "table exists\n";
  	}
  }

  /* assures the existence of all required database tables */
  function checkTables($db){
    try {
      checkConfigTable($db);
      checkUrlsTable($db);
      checkTagsTable($db);
      checksessionsTable($db);
    } catch (PDOException $pdoex){
      echo $pdoex->getMessage();
    }
  }

  session_start();

  $db = connectToDb($host,$database,$user,$pass);

  checkTables($db);

  $db = null; // close databse connection

?>
