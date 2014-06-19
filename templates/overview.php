<table class="appointments">
  <tr class="appointment">
    <th class="datestart">Start date</th>
    <th class="dateend">end date</th>
    <th class="description">description</th>
    <th class="coords">coordinates</th>
    <th class="tags">tags</th>
  </tr>

<?php
foreach ($appointments as $appointment){
  print '<tr class="appointment">'.PHP_EOL;
  print '  <th class="datestart">'.$appointment->start.'</th>'.PHP_EOL;
  print '  <th class="dateend">'.$appointment->end.'</th>'.PHP_EOL;
  print '  <th class="description">'.$appointment->description.'</th>'.PHP_EOL;
  print '  <th class="coords">'.$appointment->coords.'</th>'.PHP_EOL;
  print '  <th class="tags">';
  foreach ($appointment->tags as $tag){
    print $tag->text." ";
  }
  print '</th>'.PHP_EOL;  
}
?>

</table>
