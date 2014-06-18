<?php

  require 'config/db.php';

  try {
    $db = new PDO("mysql:host=$host;dbname=$database", $user, $pass);
  } catch (PDOException $pdoex) {
    die($pdoex->getMessage());
  }

  print "databse opened"; 
  $db=null; // close

?>
