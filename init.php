<?php
  // get full path
  define("OCC_ROOT", realpath(dirname(__FILE__)));
  define("CRLF","\r\n");
  require 'config/db.php';
  require 'locale/de.php';
  require 'functions.php';
  require 'parser.php';
  require 'db_functions.php';
  
  session_start();

  if (isset($_SESSION) && !isset($_SESSION['country'])){
  	$_SESSION['country']='DE';
  }
  $countries=array(	'DE'  => loc('Germany'),
  									'UTC' => 'UTC');


  switch ($_SESSION['country']){
    case 'DE':
      date_default_timezone_set('Europe/Berlin');
      break;
    default:
      date_default_timezone_set('UTC');
  }
  
  function occ_autoload($class_name) {
    $path = OCC_ROOT . "/include/class." . $class_name . ".php";
//    echo "occ_autoload called for $class_name\n";
    if (file_exists($path)) {
      require_once($path);
    } else {
      return false;
    }
  }

  $warnings = "";
  $notifications = "";
  
  /* default time format used in:
   *  appointment->sendToGrical */
  $db_time_format='Y-m-d H:i:s';
  
  spl_autoload_register('occ_autoload');
  
  $db = connectToDb($host,$database,$user,$pass);

  checkTables($db);

  $format='html';
  $limit=null;
  
  if (isset($_GET['format'])){
  	if ($_GET['format']=='ical'){
  		//header('Content-type: text/calendar; charset=utf-8');
  		//header('Content-Disposition: inline; filename=calendar.ics');
  		$format='ical';
  	}
  	if ($_GET['format']=='webdav'){
  		$format='webdav';
  	}
  }
  
  if (isset($_GET['limit'])){
  	$limit=(int)$_GET['limit'];
  }
  
  if (isset($_GET['debug'])){
  	$_SESSION['debug']=$_GET['debug'];
  }
  
  if (isset($_POST['country']) && array_key_exists($_POST['country'],$countries)){
  	$_SESSION['country']=$_POST['country'];
  }
  
?>
