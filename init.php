<?php

  require 'config/db.php';

  /* this was written using http://code.tutsplus.com/tutorials/why-you-should-be-using-phps-pdo-for-database-access--net-12059 */
  function connectToDb($host,$database,$user,$pass){
    try {
      $db = new PDO("mysql:host=$host;dbname=$database", $user, $pass, array(PDO::ATTR_PERSISTENT => true)); // open db connection and cache it
      print "databse opened";
      return $db;
    } catch (PDOException $pdoex) {
      die($pdoex->getMessage());
    }
  }

  session_start();

  $db = connectToDb($host,$database,$user,$pass);

  $db = null; // close databse connection

?>
