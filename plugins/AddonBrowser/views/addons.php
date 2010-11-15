<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title') ?></h1>
<div class="Info"><?php echo T('Addons allow you to add functionality to your site.'); ?></div>
<?php
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
   foreach ($this->Data('Sections') as $Key => $Name) {
      echo '<li',$this->Data('Section') == $Key ? ' class="Active"' : '','>',
         Anchor(T($Name), "settings/addons/$Key?".$this->Data('_Query')),
         "</li>\n";
   }
   ?>
   </ul>
</div>
<?php echo $this->Form->Errors(); ?>
<table class="AltRows">
   <thead>
      <tr>
         <th><?php echo T('Addon'); ?></th>
         <th><?php echo T('Description'); ?></th>
      </tr>
   </thead>
   <tbody>
   <?php
   $Addons = (array)$this->Data('Addons', array());
   $Alt = FALSE;
   foreach ($Addons as $Addon):
      $RowClass = '';

      if (GetValue('Enabled', $Addon))
         $RowClass .= ' Enabled';
      if ($Alt)
         $RowClass .= ' Alt';
      ?>
      <tr class="More <?php echo $RowClass; ?>">
         <th><?php echo Gdn_Format::Html(GetValue('Name', $Addon)) ?></th>
         <td class="Alt"><?php echo Gdn_Format::Html(GetValue('Description', $Addon, '')); ?></td>
      </tr>
      <tr class="<?php echo $RowClass; ?>">
         <td class="Info">
         <?php
         $Section = strtolower($this->Data('Section'));
         parse_str($this->Data('_Query'), $Query);
         $Query['Slug'] = AddonSlug($Addon);
         $Query['TransientKey'] = Gdn::Session()->TransientKey();

         if (GetValue('Enabled', $Addon)) {
            $Href = Url("/settings/addons/$Section/disable?".http_build_query($Query));
            echo "<a href=\"$Href\" class=\"SmallButton\">", T('Disable'), '</a>';
         } elseif (GetValue('Downloaded', $Addon)) {
            $Href = Url("/settings/addons/$Section/enable?".http_build_query($Query));
            echo "<a href=\"$Href\" class=\"SmallButton\">", T('Enable'), '</a>';
         } else {
            $Href = Url("/settings/addons/$Section/download?".http_build_query($Query));
            echo "<a href=\"$Href\" class=\"SmallButton\">", T('Download'), '</a>';
         }
         ?>
         </td>
         <td class="Info">
         <?php
            echo '<span class="Tag '.$Addon['Type'].'Tag">'.T($Addon['Type'] == 'Locale' ? '_Locale' : $Addon['Type']).'</span>';
         ?>
         </td>
      </tr>
      <?php
   endforeach;
   ?>
   </tbody>
</table>
<?php
echo $this->Data('_Pager')->ToString();