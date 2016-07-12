<?php if (!defined('APPLICATION')) exit(); ?>
<?php Gdn_Theme::assetBegin('Help'); ?>
   <?php WriteRevisions($this, 'css'); ?>
   <div class="Help Aside">
      <h2>Help</h2>
      <div class="InfoBox">
         <div>If you are new to HTML and/or CSS, here are some links you should check out:</div>
         <?php
         echo '<ul><li>'.Anchor('Our Custom HTML Documentation', 'http://vanillaforums.com/blog/help-tutorials/how-to-use-custom-theme-part-1-edit-html/', '', array('target' => '_blank')).'</li>';
         echo '<li>'.Anchor('Our Custom CSS Documentation', 'http://vanillaforums.com/help/customcss', '', array('target' => '_blank')).'</li>';
         echo '<li>'.Anchor("W3C School's CSS Tutorial", 'http://www.w3schools.com/Css', '', array('target' => '_blank')).'</li>';
         echo '<li>'.Anchor("HTML Dog's CSS Beginner Tutorial", 'http://htmldog.com/guides/cssbeginner', '', array('target' => '_blank')).'</li></ul>';
         ?>
      </div>
   </div>
<?php Gdn_Theme::assetEnd(); ?>
<?php
$this->Form->AddHidden('CurrentTab', $CurrentTab);
echo $this->Form->Open();
echo $this->Form->Errors();
$CurrentTab = $this->Form->GetFormValue('CurrentTab', GetValue(1, $this->RequestArgs, 'html'));
if (!in_array($CurrentTab, array('html', 'css')))
$CurrentTab = 'html';
?>
<div class="header-menu js-custom-theme-menu">
   <a href="<?php echo url('settings/customtheme/#'); ?>" class="js-custom-html <?php echo $CurrentTab == 'html' ? 'active' : ''; ?>"><?php echo t('Edit HTML'); ?></a>
   <a href="<?php echo url('settings/customtheme/#'); ?>" class="js-custom-css <?php echo $CurrentTab == 'html' ? '' : 'active'; ?>"><?php echo t('Edit CSS'); ?></a>
</div>
<div class="toolbar full-border">
   <div class="text-input-button toolbar-main">
      <?php
      echo wrap($this->Form->Label('Revision Label:', 'Label'), 'div', ['class' => 'label-wrap']);
      echo $this->Form->TextBox('Label');
      if (C('Plugins.CustomTheme.Enabled')) {
         echo $this->Form->Button('Apply', array('class' => 'btn btn-primary Apply'));
      } else {
         echo anchor('Apply', 'settings/customthemeupgrade/', 'btn btn-primary js-modal');
      } ?>
   </div>
   <div class="buttons">
      <?php echo $this->Form->Button('Preview', array('class' => 'btn btn-secondary TextButton')); ?>
   </div>
</div>
<div class="padded CustomCSSContainer<?php echo $CurrentTab == 'html' ? ' hidden' : ''; ?>">
   <ul>
      <li>
         <div class="CustomThemeForm">
            <?php
            echo $this->Form->TextBox('CustomCSS', array('MultiLine' => TRUE, 'class' => 'TextBox CustomThemeBox Autogrow'));
            ?>
         </div>
      </li>
   </ul>
</div>
<div class="padded CustomHtmlContainer<?php echo $CurrentTab == 'html' ? '' : ' hidden'; ?>">
   <ul>
      <li>
         <div class="CustomThemeForm">
            <?php
            echo $this->Form->TextBox('CustomHtml', array('MultiLine' => TRUE, 'class' => 'TextBox CustomThemeBox Autogrow'));
            ?>
      </li>
   </ul>
</div>
<div class="revisions revisions-content">
<?php WriteRevisions($this, 'html'); ?>
</div>
<?php
echo $this->Form->Close();

function WriteRevisions($Sender, $Tab = '') {
   $Data = GetValue('RevisionData', $Sender->Data);
   $LiveRevisionID = GetValue('LiveRevisionID', $Sender->Data);
   if (!$Data || $Data->NumRows() == 0)
      return;

   ?>
   <h2 class="recent-revisions-heading">Recent Revisions</h2>
   <div class="InfoBox RecentRevisions">
      <?php
      $LastDay = '';
      foreach ($Data->Result() as $Row) {
         $Day = date('M jS, Y', Gdn_Format::ToTimeStamp($Row->DateInserted));
         if ($Day != $LastDay) {
            echo "<div class=\"NewDay\">$Day</div>";
            $LastDay = $Day;
         }

         echo '<div class="Revision'.($Row->RevisionID == $LiveRevisionID ? ' LiveRevision' : '').'">'
             .Anchor('&rarr; '.date("g:i:sa", Gdn_Format::ToTimeStamp($Row->DateInserted)), 'settings/customtheme/revision/'.$Tab.'/'.$Row->RevisionID)
             .($Row->Label ? htmlspecialchars($Row->Label).' ' : '');

         if ($Row->Live == 1)
            echo ' Live Version';
         elseif ($Row->Live == 2)
            echo ' Previewing';
         echo '</div>';
      }
      ?>
      <div class="NewDay">
         <?php echo anchor(t('Revert to Original Version'), 'settings/customtheme/revision/'.$Tab.'/0', 'btn btn-sm btn-secondary'); ?>
      </div>
   </div>
   <?php
}
