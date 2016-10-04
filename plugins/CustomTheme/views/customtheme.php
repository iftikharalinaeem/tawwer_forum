<?php if (!defined('APPLICATION')) exit(); ?>
<?php Gdn_Theme::assetBegin('Help'); ?>
<?php WriteRevisions($this, 'css'); ?>
   <div class="help">
      <h2><?php echo t('Need more help?'); ?></h2>
      <p><?php echo sprintf(t('Check out our %s'), Anchor('Vanilla Forums Theming Guide', 'https://blog.vanillaforums.com/help/vanilla-custom-themes/', '', array('target' => '_blank'))); ?></p>
      <p><?php echo t('If you are new to HTML and/or CSS, here are some tutorials to get you started:'); ?></p>
      <?php
      echo '<ul><li>'.Anchor("W3C School's CSS Tutorial", 'http://www.w3schools.com/Css', '', array('target' => '_blank')).'</li>';
      echo '<li>'.Anchor("HTML Dog's CSS Beginner Tutorial", 'http://htmldog.com/guides/cssbeginner', '', array('target' => '_blank')).'</li></ul>';
      ?>
   </div>
<?php Gdn_Theme::assetEnd(); ?>
<?php
echo $this->Form->Open();
$CurrentTab = $this->Form->GetFormValue('CurrentTab', GetValue(1, $this->RequestArgs, 'html'));
if (!in_array($CurrentTab, array('html', 'css'))) {
   $CurrentTab = 'html';
}
$this->Form->AddHidden('CurrentTab', $CurrentTab);
?>
<div class="header-menu js-custom-theme-menu">
      <a href="<?php echo url('settings/customtheme/#'); ?>" class="js-custom-html <?php echo $CurrentTab == 'html' ? 'active' : ''; ?>"><?php echo t('Edit HTML'); ?></a>
      <a href="<?php echo url('settings/customtheme/#'); ?>" class="js-custom-css <?php echo $CurrentTab == 'html' ? '' : 'active'; ?>"><?php echo t('Edit CSS'); ?></a>
   </div>
   <?php echo $this->Form->Errors(); ?>
   <div class="toolbar">
      <div class="text-input-button toolbar-main">
         <?php
         echo wrap($this->Form->Label('Revision Label:', 'Label'), 'div', ['class' => 'label-wrap']);
         echo $this->Form->TextBox('Label');
         if (C('Plugins.CustomTheme.Enabled')) {
            echo $this->Form->Button('Apply', array('class' => 'btn btn-primary btn-apply'));
         } else {
            echo anchor('Apply', 'settings/customthemeupgrade/', 'btn btn-primary js-modal');
         } ?>
      </div>
      <div class="buttons">
         <?php echo $this->Form->Button('Preview', array('class' => 'btn btn-secondary btn-preview')); ?>
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
<?php WriteRevisions($this, 'html'); ?>
<?php
echo $this->Form->Close();

function WriteRevisions($Sender, $Tab = '') {
   $Data = GetValue('RevisionData', $Sender->Data);
   $LiveRevisionID = GetValue('LiveRevisionID', $Sender->Data);
   if (!$Data || $Data->NumRows() == 0)
      return;
   ?>
   <div class="control-panel">
      <div class="control-panel-heading">Recent Revisions</div>
      <div class="control-panel-body">
         <?php
         $LastDay = '';
         foreach ($Data->Result() as $Row) { ?>
            <?php
            $Day = date('M jS, Y', Gdn_Format::ToTimeStamp($Row->DateInserted));
            if ($Day != $LastDay) {
               echo "<div class=\"NewDay control-panel-subheading\">$Day</div>";
               $LastDay = $Day;
            }
            ?>
            <ul class="control-panel-list">
               <li class="control-panel-list-item <?php echo 'Revision'.($Row->RevisionID == $LiveRevisionID ? ' LiveRevision' : ''); ?>">
                  <?php
                  echo anchor('&rarr; '.date("g:ia", Gdn_Format::ToTimeStamp($Row->DateInserted)), 'settings/customtheme/revision/'.$Tab.'/'.$Row->RevisionID);
                  echo ($Row->Label ? ' <span class="italic truncate control-panel-list-item-label">'.htmlspecialchars($Row->Label).'</span> ' : '');
                  if ($Row->Live == 1) {
                     echo dashboardSymbol('star-empty', t('Live'), 'icon-text');
                  } elseif ($Row->Live == 2) {
                     echo dashboardSymbol('eye', t('Previewing'), 'icon-text');
                  } ?>
               </li>
            </ul>
            <?php
         }
         ?>
      </div>
      <div class="control-panel-footer">
         <?php echo anchor(t('Revert to Original Version'), 'settings/customtheme/revision/'.$Tab.'/0', 'btn btn-control-panel'); ?>
      </div>
   </div>
   <?php
}
