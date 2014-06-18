<?php

  require 'config/db.php';

  function connectToDb($host,$database,$user,$pass){
    try {
      $db = new PDO("mysql:host=$host;dbname=$database", $user, $pass);
    } catch (PDOException $pdoex) {
      die($pdoex->getMessage());
    }
    print "databse opened";
  }

  connectToDb($host,$database,$user,$pass);

  $db=null; // close

?>
