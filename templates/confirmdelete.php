<div class="confirmation">
<?php echo str_replace('%apptitle', $appointment->title, loc('Seriously, delete "%apptitle"?'));?><br/>
<?php 
 echo '<a href="?delete='.$appointment->id.'&confirm=yes">'.loc('Yes').'</a>';
?>
&nbsp;&nbsp;&nbsp;
<a href="."><?php echo loc('No')?></a>
</div>
