<?php
  // get full path
  define("OCC_ROOT", realpath(dirname(__FILE__)));
  define("CRLF","\r\n");
  define("NL","\n");
  require 'config/db.php';
  require 'locale/de.php';
  require 'functions.php';
  require 'db_functions.php';
  
  ini_set('mbstring.substitute_character', "none");
  
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
//    echo "occ_autoload called for $class_name\n";
    $path = OCC_ROOT . "/include/class." . strtolower($class_name) . ".php";
    if (file_exists($path)) {
      require_once($path);
      return;
    }       	
    $path = OCC_ROOT . "/parsers/class." . strtolower($class_name) . ".php";
    if (file_exists($path)) {
      require_once($path);
      return;
    }       	
    return false;
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
  
  $selected_tags = array();  
  if (isset($_GET['tag'])){
  	$selected_tags=explode(' ', $_GET['tag']);
  }
  
  if (isset($_POST['ical']) && is_numeric($_POST['ical'])){
  	$_GET['show']=$_POST['ical'];
  	$_GET['format']='ical';
  }
  
  
  if (isset($_GET['format'])){
  	if ($_GET['format']=='ical'){
  		$filename='opencloudcal_all';
  		if (!empty($selected_tags)){
  			$filename=implode('+', $selected_tags);
  		}
  		if (!empty($_GET['show'])){
  			$filename='opencloudcal-'.$_GET['show'];
  		}
  		
  		header('Content-type: text/calendar; charset=utf-8');
  		header('Content-Disposition: inline; filename='.$filename.'.ics');
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
