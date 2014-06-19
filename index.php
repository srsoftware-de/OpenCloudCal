<?php

  require 'init.php';

  $selected_tags = array();
  
  $appointments = appointment::loadAll($selected_tags);
  print_r($appointments);

  $db = null; // close database connection
?>
