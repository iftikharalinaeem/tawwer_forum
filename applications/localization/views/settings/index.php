<?php if (!defined('APPLICATION')) return; ?>
<style>
   .GridList {
      overflow: hidden;
   }
   .GridList li {
      width: 50%;
      float: left;
      min-height: 100px;
   }
</style>

<h1>Localization Editor Settings</h1>

<div class="PageInfo">
   <h2>Heads Up!</h2>
   <p><b>This settings page is meant to be run on your localhost, not the live server.</b></p>
   <p>In order to use some of the functionality here you must make your /locales folder writeable.</p>
</div>

<h3>Tools</h3>
<?php 
echo $this->Form->Open(); 
echo $this->Form->Errors();
?>
<ul class="GridList">
   <li>
      <?php
      echo $this->Form->Label('Download Locale List');
      echo '<div class="Info2">Download the list of locales from vanillaforums.org/addons.</div>';
      echo Anchor('Download Locale List', '/localization/settings/downloadlocalelist', 'Button'); 
      ?>
   </li>
   <li>
      <?php
      echo $this->Form->Label('Download Locale Pack', 'AddonKey');
      echo '<div class="Info2">Download a locale pack from vanillaforums.org/addons.</div>';
      echo $this->Form->DropDown('AddonKey', ConsolidateArrayValuesByKey($this->Data('LocaleAddons'), 'AddonKey', 'NameAndStatus'), array('IncludeNull' => TRUE)); 
      echo $this->Form->Button('DownloadLocalePack', array('value' => 'Download'));
      ?>
   </li>
   <li>
      <?php
      echo $this->Form->Label('Save Translations from a Locale Pack', 'LocalePack');
      echo $this->Form->DropDown('LocalePack', ConsolidateArrayValuesByKey($this->Data('LocalePacks'), 'Index', 'Name'), array('IncludeNull' => TRUE));
      echo $this->Form->Button('SaveLocalePack', array('value' => 'Save'));
      ?>
   </li>
   <li>
      <?php
      echo $this->Form->Label('Save Captured Definitions', 'SaveCaptured');
      ?>
      <div class="Info2">If you have the locale developer plugin enabled you can save all of its captured translations.</div> <a class="Button Hijack" href="<?php echo Url('/localization/settings/savelocaledeveloper'); ?>">Save Locale Developer Files</a>
   </li>
   <li>
      <?php
      echo $this->Form->Label('Create a localization CSV', 'CreateFile');
      echo $this->Form->DropDown('LocaleToCreate', ConsolidateArrayValuesByKey($this->Data('DbLocales'), 'Locale', 'Name'), array('IncludeNull' => FALSE));
      echo $this->Form->Button('CreateFile', array('value' => 'Create'));
      ?>
   </li>
</ul>
<?php echo $this->Form->Close();