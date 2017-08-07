<?php if (!defined('APPLICATION')) exit();

$ThemeName = arrayValue('Name', $this->CurrentThemeInfo, 'default');

echo $this->Form->open();
echo $this->Form->errors();
?>
<ul>
   <li>
      <div class="CustomCSSForm">
         <h1>Custom CSS</h1>
         <div class="Info">
            CSS (Cascading Stylesheets) allow you to change the colors, fonts, images, and layout of your forum. If you are new to CSS, there are some help references in the panel on the right.
            <?php
            if (!Gdn::config('Plugins.CustomCSS.Enabled')) {
               echo '<div class="Warning"><strong>Note:</strong> You can edit your css and preview the changes for free. If you would like to make your changes visible to the public, please purchase the '.anchor('Custom CSS Premium Upgrade', 'plugin/upgrades').'.</div>';
            }
            ?>
         </div>
         <?php
         echo $this->Form->textBox('CustomCSS', ['MultiLine' => TRUE, 'class' => 'TextBox CustomCSSBox Autogrow']);
         ?>
      </div>
      <div class="CustomCSSOptions">
         <strong>Revision Options</strong>
         <div class="Info RevisionOptions">
            <ul>
               <li>
                  <strong>How to include your Custom CSS:</strong>
                  <?php
                  $Default = Gdn::config('Plugins.CustomCSS.IncludeTheme', 'Yes');
                  echo $this->Form->radio('IncludeTheme', 'Add my css after the '.$ThemeName.' theme css.', ['value' => 'Yes', 'default' => $Default]);
                  echo $this->Form->radio('IncludeTheme', "Don't use any theme css, ONLY use mine.", ['value' => 'No', 'default' => $Default]);
                  ?>
               </li>
            </ul>
            <?php
            if (Gdn::config('Plugins.CustomCSS.Enabled'))
               echo $this->Form->button('Apply ⇥', ['Name' => 'Form/Apply']);

            echo $this->Form->button('Preview ⇡', ['Name' => 'Form/Preview']);
            ?>
         </div>
         <strong>Help</strong>
         <div class="Info">
            <div>If you are new to CSS, here are some links you should check out:</div>
            <?php
            echo '→ '.anchor('Our Custom CSS Documentation', 'http://vanillaforums.com/help/customcss', '', ['target' => '_blank']);
            echo '<br />→ '.anchor("W3C School's CSS Tutorial", 'http://www.w3schools.com/Css', '', ['target' => '_blank']);
            echo '<br />→ '.anchor("Html Dog's CSS Beginner Tutorial", 'http://htmldog.com/guides/cssbeginner', '', ['target' => '_blank']);
            ?>            
         </div>
         <?php
            $Folder = PATH_CACHE . DS . 'CustomCSS' . DS . Gdn::config('Garden.Theme', '');
            $FileArray = [];
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
               $AppliedFile = Gdn::config('Plugins.CustomCSS.Enabled') ? Gdn::config('Plugins.CustomCSS.File', '') : '';
               foreach ($FileArray as $MTime => $File) {
                  $Day = date("M jS, Y", $MTime);
                  if ($LastDay != $Day) {
                     $Files = "<div class=\"NewDay\">$LastDay</div>".$Files;
                     $LastDay = $Day;
                  }
                  $Files = '<div class="Revision'.($AppliedFile == $File.'.css' ? ' LiveRevision' : '').'">→'
                     .anchor(date("g:i:sa", $MTime), 'plugin/CustomCSS/'.$File)
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
echo $this->Form->close();

