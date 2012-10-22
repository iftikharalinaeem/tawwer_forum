<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title') ?></h1>
<div class="Info"><?php echo T('Addons allow you to add functionality to your site.'); ?></div>
<?php
parse_str($this->Data('_Query'), $Query);

$this->Form->InputPrefix = '';
echo $this->Form->Open(array('method' => 'get', 'Exclude' => array('Action', 'Search')));

echo '<ul>';

echo '<li>';
echo $this->Form->Label('Search', 'Search');
echo $this->Form->TextBox('Search');
echo $this->Form->Button('Search', array('Name' => 'Action'));
echo '</li>';

echo '</ul>';

echo $this->Form->Close();
?>
<div class="Tabs FilterTabs">
   <ul>
   <?php
   $TabQuery = $Query;
   unset($TabQuery['Page']);
   foreach ($this->Data('Sections') as $Key => $Name) {
      echo '<li',$this->Data('Section') == $Key ? ' class="Active"' : '','>',
         Anchor(T($Name), "settings/addons/$Key?".http_build_query($TabQuery)),
         "</li>\n";
   }
   ?>
   </ul>
</div>
<?php echo $this->Form->Errors(); ?>
<table class="Addons">
   <?php
   $Cols = 2;
   $Col = 0;

   $Addons = (array)$this->Data('Addons', array());
   $Alt = FALSE;
   foreach ($Addons as $Addon):
      $ID = "Addon_".AddonSlug($Addon);

      if ($Col == 0)
         echo '<tr>';

      $RowClass = 'Addon '.$Addon['Type'];

      if (GetValue('Enabled', $Addon))
         $RowClass .= ' Enabled';
      ?>
      <td id="<?php echo $ID; ?>" class="<?php echo $RowClass; ?>"><div class="Con">
         <?php
            // Write the icon.
            $IconUrl = GetValue('IconUrl', $Addon);
            if (!$IconUrl)
               $IconUrl = 'plugins/AddonBrowser/design/'.strtolower($Addon['Type']).'.png';
            echo Img($IconUrl, array('class' => 'AddonIcon'));

            // Write the name and description.
            echo '<div class="AddonInfo">';
            echo '<h2>', Gdn_Format::Html(GetValue('Name', $Addon)), '</h2>';
            echo '<p>', Gdn_Format::Html(GetValue('Description', $Addon, '')), '</p>';
            echo '</div>';

            echo '<div class="Foot">';

            // Write the buttons.
            echo '<div class="Buttons">';
            $Section = strtolower($this->Data('Section'));
            parse_str($this->Data('_Query'), $Query);
            $Query['Slug'] = AddonSlug($Addon);
            $Query['TransientKey'] = Gdn::Session()->TransientKey();

            if (GetValue('CanEnable', $Addon, TRUE)) {
               if (GetValue('Enabled', $Addon)) {
                  $Href = Url("/settings/addons/$Section/disable?".http_build_query($Query));
                  echo "<a href=\"$Href\" class=\"SmallButton\">", T('Disable'), '</a>';
               } elseif (GetValue('Downloaded', $Addon) || !$this->Data('_ShowDownloads', TRUE)) {
                  if (GetValue('CanEnable', $Addon, TRUE)) {
                     $Href = Url("/settings/addons/$Section/enable?".http_build_query($Query));
                     echo "<a href=\"$Href\" class=\"SmallButton\">", T('Enable'), '</a>';
                  }

                  if (GetValue('CanRemove', $Addon)) {
                     echo ' ';
                     $Href = Url("/settings/addons/$Section/remove?".http_build_query($Query));
                     echo "<a href=\"$Href\" class='SmallButton'>", T('Remove'), '</a>';
                  }
               } else {
                  $Href = Url("/settings/addons/$Section/download?".http_build_query($Query));
                  echo "<a href=\"$Href\" class=\"SmallButton\">", T('Download'), '</a>';
               }
            }

            if (GetValue('Enabled', $Addon) && GetValue('SettingsUrl', $Addon)) {
               $SettingsUrl = Url($Addon['SettingsUrl']);
               echo " <a href='$SettingsUrl' class='SmallButton'>", T('Settings'), '</a>';
            }

            echo '</div>';

            // Write the meta information.
            echo '<div class="Meta">';
            echo '<span class="Tag '.$Addon['Type'].'Tag">'.T($Addon['Type'] == 'Locale' ? '_Locale' : $Addon['Type']).'</span>';

            $this->EventArguments['Addon'] = $Addon;
            $this->FireEvent('AddonMeta');
            echo '</div>';

            echo '</div>';

            $Col = ($Col + 1) % $Cols;
            if ($Col == 0)
               echo '</tr>';
         ?>
         </div></td>
      <?php
   endforeach;

   // Write the remaining cells.
   if ($Col != 0) {
      for ($i = $Col; $i != 0; $i = ($i + 1) % $Cols) {
         echo '<td class="Addon Empty">&#160;</td>';
      }
      echo '</tr>';
   }
   ?>
</table>
<?php
echo $this->Data('_Pager')->ToString();