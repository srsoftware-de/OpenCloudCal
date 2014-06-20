<?php
  include 'forms.php';
?>
<form class="adddate" method="POST">
  <input type="text" name="description"/>Beschreibung<br/>
    <?php datepicker('test'); ?>
  <input type="submit" value="Neuen Termin anlegen"/><br/>
</form>
