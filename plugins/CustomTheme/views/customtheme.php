<?php if (!defined('APPLICATION')) exit();
$ThemeName = ArrayValue('Name', $this->CurrentThemeInfo, 'default');


function WriteRevisions($Folder, $CurrentFile) {
   $FileArray = array();
   $Files = '';
   if (file_exists($Folder)) {
      if ($Handle = opendir($Folder)) {
         $LastDay = '';
          while (FALSE !== ($File = readdir($Handle))) {
            if (substr($File, 0, 7) == 'custom_') {
               $FilePath = $Folder . DS . $File;
               $FileArray[filemtime($FilePath)] = $File;
            }
          }
            
         closedir($Handle);
      }
      ksort($FileArray);
      foreach ($FileArray as $MTime => $File) {
         $Day = date("M jS, Y", $MTime);
         if ($LastDay != $Day) {
            $Files = "<div class=\"NewDay\">$LastDay</div>".$Files;
            $LastDay = $Day;
         }
         $Files = '<div class="Revision'.($CurrentFile == $File ? ' LiveRevision' : '').'">&rarr;'
            .Anchor(date("g:i:sa", $MTime), 'settings/customtheme/'.$File.'/archive')
            .($CurrentFile == $File ? ' Live Version' : '')
            .'</div>'.$Files;
      }
      if (isset($Day))
         $Files = "<div class=\"NewDay\">$Day</div>".$Files;
   }
   if ($Files != '') {
      ?>
      <strong>Recent Revisions</strong>
      <div class="InfoBox RecentRevisions">
         <?php echo $Files; ?>
      </div>
      <?php
   }
}

$this->Form->AddHidden('CurrentTab', $this->CurrentTab);
echo $this->Form->Open();
?>
<h1>Custom Theme: <?php echo $this->Form->TextBox('ThemeName'); ?></h1>
<?php
echo $this->Form->Errors();
?>
<div class="Tabs CustomThemeTabs">
   <ul>
      <li class="CustomHtml<?php echo $this->CurrentTab == 'Html' ? ' Active' : ''; ?>"><?php echo Anchor(T('Edit Html'), 'settings/customtheme/#'); ?></li>
      <li class="CustomCSS<?php echo $this->CurrentTab == 'Html' ? '' : ' Active'; ?>"><?php echo Anchor(T('Edit CSS'), 'settings/customtheme/#'); ?></li>
   </ul>
</div>
<div class="Container CustomCSSContainer<?php echo $this->CurrentTab == 'Html' ? ' Hidden' : ''; ?>">
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
                     echo $this->Form->Radio('IncludeThemeCSS', 'Add my css after the '.$ThemeName.' theme css.', array('value' => 'Yes', 'default' => $Default));
                     echo $this->Form->Radio('IncludeThemeCSS', "Don't use any theme css, ONLY use mine.", array('value' => 'No', 'default' => $Default));
                     ?>
                  </li>
               </ul>
               <?php
               if (C('Plugins.CustomTheme.Enabled'))
                  echo $this->Form->Button('Apply &rarr;', array('Name' => 'Form/Apply', 'class' => 'Button Apply'));
               else
                  echo Anchor('Apply &rarr;', 'settings/customthemeupgrade/', 'Button Apply');
   
               echo $this->Form->Button('Preview &uarr;', array('Name' => 'Form/Preview'));
               ?>
            </div>
            <strong>Help</strong>
            <div class="InfoBox">
               <div>If you are new to CSS, here are some links you should check out:</div>
               <?php
               echo '&rarr; '.Anchor('Our Custom CSS Documentation', 'http://vanillaforums.com/help/customcss', '', array('target' => '_blank'));
               echo '<br />&rarr; '.Anchor("W3C School's CSS Tutorial", 'http://www.w3schools.com/Css', '', array('target' => '_blank'));
               echo '<br />&rarr; '.Anchor("Html Dog's CSS Beginner Tutorial", 'http://htmldog.com/guides/cssbeginner', '', array('target' => '_blank'));
               ?>            
            </div>
            <?php
            WriteRevisions(
               PATH_THEMES . DS . C('Garden.Theme', '') . DS . 'design',
               C('Plugins.CustomTheme.EnabledCSS', ''),
               '.css'
            );
            ?>
         </div>
      </li>
   </ul>
</div>
<div class="Container CustomHtmlContainer<?php echo $this->CurrentTab == 'Html' ? '' : ' Hidden'; ?>">
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
               <?php
               if (C('Plugins.CustomTheme.Enabled'))
                  echo $this->Form->Button('Apply &rarr;', array('Name' => 'Form/Apply', 'class' => 'Button Apply'));
               else
                  echo Anchor('Apply &rarr;', 'settings/customthemeupgrade/', 'Button Apply');
   
               echo $this->Form->Button('Preview &uarr;', array('Name' => 'Form/Preview'));
               ?>
            </div>
            <strong>Help</strong>
            <div class="InfoBox">
               <div>If you are new to HTML, here are some links you should check out:</div>
               <?php
               echo '&rarr; '.Anchor('Our Custom HTML Documentation', 'http://vanillaforums.com/blog/help-tutorials/how-to-use-custom-theme-part-1-edit-html/', '', array('target' => '_blank'));
               ?>            
            </div>
            <?php
            WriteRevisions(
               PATH_THEMES . DS . C('Garden.Theme', '') . DS . 'views',
               C('Plugins.CustomTheme.EnabledHtml', ''),
               '.tpl'
            );
            ?>
         </div>
      </li>
   </ul>
</div>
<?php
echo $this->Form->Close();

