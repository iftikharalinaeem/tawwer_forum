<?php if (!defined('APPLICATION')) exit();

$ThemeName = ArrayValue('Name', $this->CurrentThemeInfo, 'default');

echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li>
      <div class="CustomCSSForm">
         <h1>Custom CSS</h1>
         <div class="Info">
            CSS (Cascading Stylesheets) allow you to change the colors, fonts, images, and layout of your forum. If you are new to CSS, there are some help references in the panel on the right.
            <?php
            if (!Gdn::Config('Plugins.CustomCSS.Enabled')) {
               echo '<div class="Warning"><strong>Note:</strong> You can edit your css and preview the changes for free. If you would like to make your changes visible to the public, please purchase the '.Anchor('Custom CSS Premium Upgrade', 'plugin/upgrades').'.</div>';
            }
            ?>
         </div>
         <?php
         echo $this->Form->TextBox('CustomCSS', array('MultiLine' => TRUE, 'class' => 'TextBox CustomCSSBox Autogrow'));
         ?>
      </div>
      <div class="CustomCSSOptions">
         <strong>Revision Options</strong>
         <div class="Info RevisionOptions">
            <ul>
               <li>
                  <strong>How to include your Custom CSS:</strong>
                  <?php
                  $Default = Gdn::Config('Plugins.CustomCSS.IncludeTheme', 'Yes');
                  echo $this->Form->Radio('IncludeTheme', 'Add my css after the '.$ThemeName.' theme css.', array('value' => 'Yes', 'default' => $Default));
                  echo $this->Form->Radio('IncludeTheme', "Don't use any theme css, ONLY use mine.", array('value' => 'No', 'default' => $Default));
                  ?>
               </li>
            </ul>
            <?php
            if (Gdn::Config('Plugins.CustomCSS.Enabled'))
               echo $this->Form->Button('Apply ⇥', array('Name' => 'Form/Apply'));

            echo $this->Form->Button('Preview ⇡', array('Name' => 'Form/Preview'));
            ?>
         </div>
         <strong>Help</strong>
         <div class="Info">
            <div>If you are new to CSS, here are some links you should check out:</div>
            <?php
            echo '→ '.Anchor('Our Custom CSS Documentation', 'http://vanillaforums.com/help/customcss', '', array('target' => '_blank'));
            echo '<br />→ '.Anchor("W3C School's CSS Tutorial", 'http://www.w3schools.com/Css', '', array('target' => '_blank'));
            echo '<br />→ '.Anchor("Html Dog's CSS Beginner Tutorial", 'http://htmldog.com/guides/cssbeginner', '', array('target' => '_blank'));
            ?>            
         </div>
         <?php
            $Folder = PATH_CACHE . DS . 'CustomCSS' . DS . Gdn::Config('Garden.Theme', '');
            $FileArray = array();
            $Files = '';
            if (file_exists($Folder)) {
               if ($Handle = opendir($Folder)) {
                  $LastDay = '';
                   while (FALSE !== ($File = readdir($Handle))) {
                     if (substr($File, 0, 4) == 'rev_') {
                        $FilePath = $Folder . DS . $File;
                        $FileArray[filemtime($FilePath)] = substr($File, 0, strpos($File, '.'));
                     }
                   }
                     
                  closedir($Handle);
               }
               ksort($FileArray);
               $AppliedFile = Gdn::Config('Plugins.CustomCSS.Enabled') ? Gdn::Config('Plugins.CustomCSS.File', '') : '';
               foreach ($FileArray as $MTime => $File) {
                  $Day = date("M jS, Y", $MTime);
                  if ($LastDay != $Day) {
                     $Files = "<div class=\"NewDay\">$LastDay</div>".$Files;
                     $LastDay = $Day;
                  }
                  $Files = '<div class="Revision'.($AppliedFile == $File.'.css' ? ' LiveRevision' : '').'">→'
                     .Anchor(date("g:i:sa", $MTime), 'plugin/CustomCSS/'.$File)
                     .($AppliedFile == $File.'.css' ? ' Live Version' : '')
                     .'</div>'.$Files;
               }
               if (isset($Day))
                  $Files = "<div class=\"NewDay\">$Day</div>".$Files;
            }
            if ($Files != '') {
               ?>
               <strong>Recent Revisions</strong>
               <div class="Info RecentRevisions">
                  <?php echo $Files; ?>
               </div>
               <?php
            }
         ?>
      </div>
   </li>
</ul>
<?php
echo $this->Form->Close();

