<h2><?php echo loc('appointment overview');?></h2>
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
  print '  <td class="datestart">'.$appointment->start.'</th>'.PHP_EOL;
  print '  <td class="dateend">'.$appointment->end.'</th>'.PHP_EOL;
  print '  <td class="title">'.$appointment->title.'</th>'.PHP_EOL;
  print '  <td class="description">'.str_replace("\n", "<br/>", $appointment->description).'</th>'.PHP_EOL;
  print '  <td class="location">'.$appointment->location.'<br/>'.$appointment->coords.'</th>'.PHP_EOL;
  print '  <td class="tags">';
  foreach ($appointment->tags as $tag){
    print $tag->text." ";
  }
  print '</th>'.PHP_EOL;  
}
?>

</table>
