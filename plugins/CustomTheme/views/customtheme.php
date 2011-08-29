<?php if (!defined('APPLICATION')) exit();
function WriteRevisions($Sender, $Tab = '') {
   $Data = GetValue('RevisionData', $Sender->Data);
   $LiveRevisionID = GetValue('LiveRevisionID', $Sender->Data);
   if (!$Data || $Data->NumRows() == 0)
      return;
   
   ?>
   <strong>Recent Revisions</strong>
   <div class="InfoBox RecentRevisions">
   <?php
   $LastDay = '';
   foreach ($Data->Result() as $Row) {
      $Day = date('M jS, Y', Gdn_Format::ToTimeStamp($Row->DateInserted));
      if ($Day != $LastDay) {
         echo "<div class=\"NewDay\">$Day</div>";
         $LastDay = $Day;
      }

      echo '<div class="Revision'.($Row->RevisionID == $LiveRevisionID ? ' LiveRevision' : '').'">&rarr;'
         .Anchor(date("g:i:sa", Gdn_Format::ToTimeStamp($Row->DateInserted)), 'settings/customtheme/revision/'.$Tab.'/'.$Row->RevisionID)
         .($Row->RevisionID == $LiveRevisionID ? ' Live Version' : '')
      .'</div>';
   }  
   ?>
      <div class="NewDay"><?php echo Anchor(T('Original Version'), 'settings/customtheme/revision/'.$Tab.'/0'); ?></div>
   </div>
   <?php
}
$CurrentTab = $this->Form->GetFormValue('CurrentTab', GetValue(1, $this->RequestArgs, 'html'));
if (!in_array($CurrentTab, array('html', 'css')))
   $CurrentTab = 'html';
   
$this->Form->AddHidden('CurrentTab', $CurrentTab);
echo $this->Form->Open();
?>
<h1>Customize Theme</h1>
<?php
echo $this->Form->Errors();
?>
<div class="Tabs CustomThemeTabs">
   <ul>
      <li class="CustomHtml<?php echo $CurrentTab == 'html' ? ' Active' : ''; ?>"><?php echo Anchor(T('Edit Html'), 'settings/customtheme/#'); ?></li>
      <li class="CustomCSS<?php echo $CurrentTab == 'html' ? '' : ' Active'; ?>"><?php echo Anchor(T('Edit CSS'), 'settings/customtheme/#'); ?></li>
   </ul>
</div>
<div class="Container CustomCSSContainer<?php echo $CurrentTab == 'html' ? ' Hidden' : ''; ?>">
   <ul>
      <li>
         <div class="CustomThemeForm">
            <?php
            echo $this->Form->TextBox('CustomCSS', array('MultiLine' => TRUE, 'class' => 'TextBox CustomThemeBox Autogrow'));
            ?>
         </div>
         <div class="CustomThemeOptions">
            <strong>Revision Options</strong>
            <div class="InfoBox RevisionOptions">
               <ul>
                  <li>
                     <strong>How to include your Custom CSS:</strong>
                     <?php
                     $Default = C('Plugins.CustomCSS.IncludeThemeCSS', 'Yes');
                     echo $this->Form->Radio('IncludeThemeCSS', 'Add my css after the theme css.', array('value' => 'Yes', 'default' => $Default));
                     echo $this->Form->Radio('IncludeThemeCSS', "ONLY use my CSS (not recommended).", array('value' => 'No', 'default' => $Default));
                     ?>
                  </li>
               </ul>
               <div class="Buttons">
               <?php
               echo $this->Form->Button('Preview', array('class' => 'TextButton'));
               if (C('Plugins.CustomTheme.Enabled'))
                  echo $this->Form->Button('Apply', array('class' => 'Button Apply'));
               else
                  echo Anchor('Apply', 'settings/customthemeupgrade/', 'Button Apply');
   
               ?>
               </div>
            </div>
            <?php WriteRevisions($this, 'css'); ?>
            <strong>Help</strong>
            <div class="InfoBox">
               <div>If you are new to CSS, here are some links you should check out:</div>
               <?php
               echo '&rarr; '.Anchor('Our Custom CSS Documentation', 'http://vanillaforums.com/help/customcss', '', array('target' => '_blank'));
               echo '<br />&rarr; '.Anchor("W3C School's CSS Tutorial", 'http://www.w3schools.com/Css', '', array('target' => '_blank'));
               echo '<br />&rarr; '.Anchor("Html Dog's CSS Beginner Tutorial", 'http://htmldog.com/guides/cssbeginner', '', array('target' => '_blank'));
               ?>            
            </div>
         </div>
      </li>
   </ul>
</div>
<div class="Container CustomHtmlContainer<?php echo $CurrentTab == 'html' ? '' : ' Hidden'; ?>">
   <ul>
      <li>
         <div class="CustomThemeForm">
            <?php
            echo $this->Form->TextBox('CustomHtml', array('MultiLine' => TRUE, 'class' => 'TextBox CustomThemeBox Autogrow'));
            ?>
         </div>
         <div class="CustomThemeOptions">
            <strong>Revision Options</strong>
            <div class="InfoBox RevisionOptions">
               <div class="Buttons">
               <?php
               echo $this->Form->Button('Preview', array('class' => 'TextButton'));
               if (C('Plugins.CustomTheme.Enabled'))
                  echo $this->Form->Button('Apply', array('class' => 'Button Apply'));
               else
                  echo Anchor('Apply', 'settings/customthemeupgrade/', 'Button Apply');
   
               ?>
               </div>
            </div>
            <?php WriteRevisions($this, 'html'); ?>
            <strong>Help</strong>
            <div class="InfoBox">
               <div>If you are new to HTML, here are some links you should check out:</div>
               <?php
               echo '&rarr; '.Anchor('Our Custom HTML Documentation', 'http://vanillaforums.com/blog/help-tutorials/how-to-use-custom-theme-part-1-edit-html/', '', array('target' => '_blank'));
               ?>            
            </div>
         </div>
      </li>
   </ul>
</div>
<?php
echo $this->Form->Close();

