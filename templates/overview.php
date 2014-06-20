<h2><?php echo loc('appointment overview');?></h2>
<table class="appointments">
  <tr class="appointment">
    <th class="datestart"><?php echo loc('Start date'); ?></th>
    <th class="title"><?php echo loc('Title'); ?></th>
    <th class="coords"><?php echo loc('Location');?></th>
    <th class="tags"><?php echo loc('Tags'); ?></th>
  </tr>

<?php
foreach ($appointments as $appointment){
	$c=explode(',',$appointment->coords);
  print '<tr class="appointment">'.PHP_EOL;
  print '  <td class="datestart">'.$appointment->start.'</th>'.PHP_EOL;
  print '  <td class="title"><a href=".?show='.$appointment->id.'">'.$appointment->title.'</a></th>'.PHP_EOL;  
  print '  <td class="location">'.$appointment->location;
  if (count($c)==2){
		print '<br/><a href="http://www.openstreetmap.org/?mlat='.$c[0].'&mlon='.$c[1].'&zoom=15">'.$appointment->coords.'</a>';
	}
	print '</th>'.PHP_EOL;
  print '  <td class="tags">';
  foreach ($appointment->tags as $tag){
    print '<a href="?tag='.$tag->text.'">'.$tag->text.'</a> ';
  }
  print '</th>'.PHP_EOL;  
}
?>

</table>
