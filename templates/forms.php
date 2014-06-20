<?php
  function datepicker($name,$time=null){
    date_default_timezone_set('UTC');

    if ($time==null){
      $time=time();
    }
    print '<div class="datepicker">'.PHP_EOL;

    $year=date('Y');
    $selected_year=date('Y',$time);
    print '  <select name="'.$name.'_year" size="1">'.PHP_EOL;
    for ($i=0; $i<10; $i++){
      if ($year==$selected_year){
        print '    <option selected>'.$year.'</option>'.PHP_EOL;
      } else {
        print '    <option>'.$year.'</option>'.PHP_EOL;
      }
      $year+=1;
    }
    print '  </select>'.PHP_EOL;

    $month=1;
    $selected_month=date('m',$time);
    print '  <select name="'.$name.'_month" size="1">'.PHP_EOL;
    for ($i=0; $i<12; $i++){
      if ($month==$selected_month){
        print '    <option selected>'.$month.'</option>'.PHP_EOL;
      } else {
        print '    <option>'.$month.'</option>'.PHP_EOL;
      }
      $month+=1;
    }
    print '  </select>'.PHP_EOL;

    $day=1;
    $selected_day=date('d',$time);
    print '  <select name="'.$name.'_day" size="1">'.PHP_EOL;
    for ($i=0; $i<31; $i++){
      if ($day==$selected_day){
        print '    <option selected>'.$day.'</option>'.PHP_EOL;
      } else {
        print '    <option>'.$day.'</option>'.PHP_EOL;
      }
      $day+=1;
    }
    print '  </select>'.PHP_EOL;


    print '</div>'.PHP_EOL;

  }

  function timepicker($name,$time=null){
    date_default_timezone_set('UTC');

    print '<div class="timepicker">'.PHP_EOL;

    if ($time==null){
      $selected_hour=0;     
    } else {
      $selected_hour = date('H',$time);
    }
    $hour=0;
    print '  <select name="'.$name.'_hour" size="1">'.PHP_EOL;
    for ($i=0; $i<24; $i++){
      if ($hour==$selected_hour){
        print '    <option selected>'.$hour.'</option>'.PHP_EOL;
      } else {
        print '    <option>'.$hour.'</option>'.PHP_EOL;
      }
      $hour+=1;
    }
    print '  </select>'.PHP_EOL;

    if ($time==null){
      $selected_minute=0;
    } else {
      $selected_minute=date('i',$time);
    }
    $minute=0;
    print '  <select name="'.$name.'_minute" size="1">'.PHP_EOL;
    for ($i=0; $i<60; $i++){
      if ($minute==$selected_minute){
        print '    <option selected>'.$minute.'</option>'.PHP_EOL;
      } else {
        print '    <option>'.$minute.'</option>'.PHP_EOL;
      }
      $minute+=1;
    }
    print '  </select>'.PHP_EOL;

    print '</div>'.PHP_EOL;

  }


?>
