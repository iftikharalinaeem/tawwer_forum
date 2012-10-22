<?php if (!defined('APPLICATION')) return; ?>


<h1><?php echo sprintf(T('%s Locale Information'), htmlspecialchars($this->Data('Locale.Name'))); ?></h1>

<div class="Progress-Wrap">
   <?php
   $Columns = array('Core' => 'Core', 'Admin' => 'Admin', 'Addon' => 'Addons');
   
   foreach ($Columns as $Column => $Label):
      $Percent = $this->Data("Locale.Percent$Column");
      $Count = $this->Data("Locale.Count$Column");
//      $Remaining = round($Count * (1 - $Percent));
      $Total = $this->Data("Locale.Total$Column");
      $ApprovedPercent = $this->Data("Locale.PercentApproved$Column");
   ?>
      <div class="Progress-Box">
         <div class="Progress-Label">
            <?php echo T($Label); ?>
         </div>
         <div class="Progress-Percent" title="<?php echo sprintf(T('%s out of %s definitions translated.'), $Count, $Total); ?>">
            <?php echo number_format($Percent * 100, 0).'%'; ?>
         </div>
         <div class="Progress-Approved">
            <?php
            echo '<b>'.T('Approved').':</b> ';
            echo number_format($ApprovedPercent * 100, 0).'%';
            ?>
         </div>
      </div>
   <?php
   endforeach;
   ?>
</div>

<h2><?php echo T('Translation Team'); ?></h2>

<div class="P">
   <?php
   echo '<ul id="TeamList">';
   
   $InTeam = FALSE;
   foreach ($this->Data('Team', array()) as $User) {
      echo '<li>';
      if ($User['UserID'] == Gdn::Session()->UserID)
         $InTeam = TRUE;
      echo UserPhoto($User, array('ImageClass' => 'ProfilePhotoSmall'));
      echo ' ';
      echo UserAnchor($User);
      echo ' ';   
      echo '</li>';
   }
   
   echo '</ul>';
   
   if (Gdn::Session()->CheckPermission('Localization.Locales.Edit')) {
      if ($InTeam) {
         $Text = T('Leave the Translation Team');
      } elseif (count($this->Data('Team')) > 0) {
         $Text = T('Join the Translation Team', 'Join the Transltion Team!');
      } else {
         $Text = T('Be the First Person on the Translation Team', 'Be the First Person on the Translation Team!');
      }
      
      $Args = array(
          'locale' => $this->Data('Locale.Locale'),
          'tk' => Gdn::Session()->TransientKey(),
          'join' => !$InTeam);
      echo '<div class="Buttons">'.Anchor($Text, '/localization/jointeam?'.http_build_query($Args), '').'</div>';
   }
   ?>
</div>

<?php 
Gdn_Theme::AssetBegin('Panel');

$LocaleCode = rawurlencode($this->Data('Locale.Locale'));

if ($LocaleCode == 'en-CA') {
   echo '<div class="Box">';
   echo '<h4>'.T("Don't Translate This!").'</h4>';
   echo '<p>'.T('This is the default locale which means you can\'t translate it.').'</p>';

   echo '</div>';
} else {
   echo Anchor(
      T('Translate').'<small>'.T('Translate the missing definitions in this locale.').'</small>',
      "/localization/translate/$LocaleCode",
      'BigButton2 TranslateButton');

   echo Anchor(
      T('Approve').'<small>'.T('Approve the translations for this locale.').'</small>',
      "/localization/approve/$LocaleCode",
      'BigButton2 ApproveButton');

   echo Anchor(
      T('Browse').'<small>'.T('Browse through all of this locale\'s translations.').'</small>',
      "/localization/browse/$LocaleCode",
      'BigButton2 BrowseButton');
}

Gdn_Theme::AssetEnd();
?>