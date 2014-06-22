<?php
  include 'forms.php';
  if (!isset($appointment)){
  	$appointment=false;
  }
?>
<form class="adddate" method="POST" action=".">
  <?php if ($appointment){
  	echo '<input type="hidden" name="editappointment[id]" value="'.$appointment->id.'" />'; 
  }?>
  <div class="left">
  	<div id="title">
    	<?php echo loc('title').'<input type="text" name="editappointment[title]"';
  			if ($appointment){
  		  	echo ' value="'.$appointment->title.'"';
  			}
  			echo '/>'; ?> 
  	</div>
  	<div id="location">
    	<?php echo loc('location').'<input type="text" name="editappointment[location]"';
  			if ($appointment){
  		  	echo ' value="'.$appointment->location.'"';
  			}
  			echo '/>'; ?> 
  	</div>
  	<div id="coordinates">
    	<?php echo loc('coordinates').'<input type="text" id="coords" name="editappointment[coordinates]"';
  			if ($appointment){
  		  	echo ' value="'.implode(', ',$appointment->coords).'"';
  			}
  			echo '/>'; ?> 
  	</div>
  	<div id="description">
    	<?php echo loc('description'); ?><textarea name="editappointment[description]"><?php 
    		if ($appointment){
  		  	echo $appointment->description;
  			}
				?></textarea>  		 
  	</div>  
  	<div id="tags">
			<?php echo loc('tags').'<input type="text" name="editappointment[tags]"';
  			if ($appointment){
  		  	echo ' value="';
  		  	foreach ($appointment->tags as $tag){
						echo $tag->text." ";
					}
  		  	echo'"';
  			}
  			echo '/>'; ?> 
  	</div>
  	
  </div>
  <div class="right">
  	<?php include 'openlayers.php'; ?>
  	<div class="start">
      <?php
      echo loc('start date');
      if ($appointment){
				datepicker('editappointment[start]',$appointment->start);
			} else {
      	datepicker('editappointment[start]');
      }
      echo loc('start time'); 
      if ($appointment){
				timepicker('editappointment[start]',$appointment->start);
			} else {
				timepicker('editappointment[start]');
			}?>
  	</div>
  	<div class="end">
    	<?php echo loc('end date (optional)');
      if ($appointment){
				datepicker('editappointment[end]',$appointment->end);
			} else {
      	datepicker('editappointment[end]');
      }
      echo loc('end time');
      if ($appointment){
      	timepicker('editappointment[end]',$appointment->end);
      } else {
      	timepicker('editappointment[end]');
      } ?>
  	</div>  	  	
  </div>    
  <div class="submit">
		  <input type="checkbox" id="addsession" name="addsession" />
		  <label for="addsession">
				<?php echo loc('Add a session to this appointment in the next step.'); ?>
			</label>
			<input type="checkbox" id="addlink" name="addlink" />
			<label for="addlink">			
				<?php echo loc('Add a link to this appointment in the next step.'); ?>
			</label>  
  	<?php echo '<input type="submit" value="'.loc('save changes').'"/><br/>'.PHP_EOL; ?>
  </div>
</form>
