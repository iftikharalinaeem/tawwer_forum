<?php if (!defined('APPLICATION')) exit();

$pluginEnabled = c('Plugins.CustomTheme.Enabled', false);
$cssEnabled = $pluginEnabled;
$htmlEnabled = $pluginEnabled && !c('Plugins.CustomTheme.DisableHtml', false);

Gdn_Theme::assetBegin('Help');
WriteRevisions($this, 'css');
Gdn_Theme::assetEnd();

$bloglink = sprintf(t('Check out our %s'), anchor('Vanilla Forums Theming Guide', 'https://blog.vanillaforums.com/help/vanilla-custom-themes/', '', ['target' => '_blank']));

$links .= '<p>'.t('If you are new to HTML and/or CSS, here are some tutorials to get you started:').'</p>';
$links .= '<ul>';
$links .= '<li>'.anchor(t('Vanilla\'s Theming Documentation'), 'http://docs.vanillaforums.com/developer/theming/', '', ["target" => "_blank"]).'</li>';
$links .= '<li>'.anchor("W3C School's CSS Tutorial", 'http://www.w3schools.com/Css', '', ['target' => '_blank']).'</li>';
$links .= '<li>'.anchor("HTML Dog's CSS Beginner Tutorial", 'http://htmldog.com/guides/cssbeginner', '', ['target' => '_blank']).'</li>';
$links .= '</ul>';

helpAsset(t('Need More Help?'), $bloglink);
helpAsset(t('Even More Help?'), $links);

echo $this->Form->open();


if ($htmlEnabled && $cssEnabled) {
   $CurrentTab = $this->Form->getFormValue('CurrentTab', val(1, $this->RequestArgs, 'html'));

   if (!in_array($CurrentTab, ['html', 'css'])) {
      $CurrentTab = 'html';
   }

} else {
   if ($htmlEnabled) {
      $CurrentTab = 'html';
   } else {
      if ($cssEnabled) {
         $CurrentTab = 'css';
      }
   }
}


$cssClass = 'header-menu-item js-custom-html';

$htmlAttr = [
    'aria-selected' => ($CurrentTab == 'html') ? 'true' : 'false',
    'class' => ($CurrentTab == 'html') ? $cssClass.' active' : $cssClass,
    'aria-controls' => 'customHtmlContainer',
    'role' => 'tab',
];

$cssClass = 'header-menu-item js-custom-css';

$cssAttr = [
    'aria-selected' => ($CurrentTab == 'html') ? 'false' : 'true',
    'class' => ($CurrentTab == 'html') ? $cssClass : $cssClass.' active',
    'aria-controls' => 'customCssContainer',
    'role' => 'tab',
];

?>
<div role="tablist" class="header-menu js-custom-theme-menu">

   <?php if ($htmlEnabled) { ?>
      <div <?php echo attribute($htmlAttr); ?>><?php echo t('Edit HTML'); ?></div>
   <?php } ?>

   <?php if ($cssEnabled) { ?>
      <div <?php echo attribute($cssAttr); ?>><?php echo t('Edit CSS'); ?></div>
   <?php } ?>
</div>
<?php
echo $this->Form->errors();
echo $this->Form->hidden('CurrentTab', $CurrentTab);
?>
<div class="toolbar">
   <div class="text-input-button toolbar-main">
      <?php
      echo wrap($this->Form->label('Revision Label:', 'Label'), 'div', ['class' => 'label-wrap']);
      echo $this->Form->textBox('Label');
      if ($cssEnabled) {
         echo $this->Form->button('Apply', ['class' => 'btn btn-primary btn-apply']);
      } else {
         echo anchor('Apply', 'settings/customthemeupgrade/', 'btn btn-primary js-modal');
      } ?>
   </div>
   <div class="buttons">
      <?php echo $this->Form->button('Preview', ['class' => 'btn btn-secondary btn-preview']); ?>
   </div>
</div>
<section id="customCssContainer" class="padded <?php echo $CurrentTab == 'html' ? ' hidden' : ''; ?>">
   <h1 class="hidden"><?php echo t('Edit CSS'); ?></h1>
   <ul>
      <li>
         <div class="CustomThemeForm">
            <?php
            echo $this->Form->textBox('CustomCSS', ['MultiLine' => true, 'class' => 'TextBox CustomThemeBox Autogrow']);
            ?>
         </div>
      </li>
   </ul>
</section>
<section id="customHtmlContainer" class="padded <?php echo $CurrentTab == 'html' ? '' : ' hidden'; ?>">
   <ul>
      <h1 class="hidden"><?php echo t('Edit HTML'); ?></h1>
      <li>
         <div class="CustomThemeForm">
            <?php
            echo $this->Form->textBox('CustomHtml', ['MultiLine' => true, 'class' => 'TextBox CustomThemeBox Autogrow']);
            ?>
      </li>
   </ul>
</section>
<?php writeRevisions($this, 'html');
echo $this->Form->close();

function writeRevisions($sender, $tab = '') {
   $data = val('RevisionData', $sender->Data);
   $liveRevisionID = val('LiveRevisionID', $sender->Data);
   if (!$data || $data->numRows() == 0) {
      return;
   }
   ?>
   <section class="control-panel">
      <h2 class="control-panel-heading">Recent Revisions</h2>
      <div class="control-panel-body">
         <?php
         $lastDay = '';
         foreach ($data->result() as $row) { ?>
            <?php
            $day = date('M jS, Y', Gdn_Format::toTimeStamp($row->DateInserted));
            if ($day != $lastDay) {
               echo "<div class=\"NewDay control-panel-subheading\">$day</div>";
               $lastDay = $day;
            }
            ?>
            <ul class="control-panel-list">
               <li class="control-panel-list-item <?php echo 'Revision'.($row->RevisionID == $liveRevisionID ? ' LiveRevision' : ''); ?>">
                  <?php
                  echo anchor('&rarr; '.date("g:ia", Gdn_Format::toTimeStamp($row->DateInserted)), 'settings/customtheme/revision/'.$tab.'/'.$row->RevisionID);
                  echo ($row->Label ? ' <span class="italic truncate control-panel-list-item-label">'.htmlspecialchars($row->Label).'</span> ' : '');
                  if ($row->Live == 1) {
                     echo dashboardSymbol('star-empty', 'icon-text', ['alt' => t('Live')]);
                  } elseif ($row->Live == 2) {
                     echo dashboardSymbol('eye', 'icon-text', ['alt' => t('Previewing')]);
                  } ?>
               </li>
            </ul>
            <?php
         }
         ?>
      </div>
      <div class="control-panel-footer">
         <?php echo anchor(t('Revert to Original Version'), 'settings/customtheme/revision/'.$tab.'/0', 'btn btn-control-panel'); ?>
      </div>
   </section>
   <?php
}
