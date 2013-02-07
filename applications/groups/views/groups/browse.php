<h1><?php echo $this->Title(); ?></h1>

<div class="PageControls Top">
<?php
echo PagerModule::Write();
?>
</div>

<?php
WriteGroupCards($this->Data('Groups'));
?>

<div class="PageControls Bottom">
<?php
echo PagerModule::Write();
?>
</div>