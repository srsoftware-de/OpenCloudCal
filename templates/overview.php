<table class="appointments">
  <tr class="appointment">
    <th class="datestart"><?php echo loc('Start date'); ?></th>
    <th class="dateend"><?php echo loc('End date'); ?></th>
    <th class="title"><?php echo loc('Title'); ?></th>
    <th class="description"><?php echo loc('Description'); ?></th>
    <th class="coords"><?php echo loc('Location');?></th>
    <th class="tags"><?php echo loc('Tags'); ?></th>
  </tr>

<?php
foreach ($appointments as $appointment){
  print '<tr class="appointment">'.PHP_EOL;
  print '  <th class="datestart">'.$appointment->start.'</th>'.PHP_EOL;
  print '  <th class="dateend">'.$appointment->end.'</th>'.PHP_EOL;
  print '  <th class="title">'.$appointment->title.'</th>'.PHP_EOL;
  print '  <th class="description">'.$appointment->description.'</th>'.PHP_EOL;
  print '  <th class="location">'.$appointment->location.'<br/>'.$appointment->coords.'</th>'.PHP_EOL;
  print '  <th class="tags">';
  foreach ($appointment->tags as $tag){
    print $tag->text." ";
  }
  print '</th>'.PHP_EOL;  
}
?>

</table>
