<?php if (!defined('APPLICATION')) return;

echo '<h1>'.$this->Data('Title').'</h1>';
echo '<p>'.T('Welcome to the Vanilla community translation site.', 
            'Welcome to the Vanilla community translation site. This site helps you translate Vanilla into your own language in a community driven, collaborative way.').'</p>';

?>
<table class="LocaleTable" summary="<?php echo T('The locales available for translation in Vanilla and there current % completeness.'); ?>">
   <caption><?php echo T('Current Locales'); ?></caption>
   <thead>
      <tr>
         <th>&#160</th>
         <th colspan="3" class="GroupColumn">
            % Complete
         </th>
      </tr>
      <tr>
         <th>Locale</th>
         <th class="PercentColumn">Core</th>
         <th class="PercentColumn">Dashboard</th>
         <th class="PercentColumn">Addons</th>
      </tr>
   </thead>
   <tbody>
      <?php foreach ($this->Data('Locales') as $Row): ?>
      <tr class="<?php echo Alternate('Alt', '', ''); ?>">
         <td><?php echo Anchor(htmlspecialchars($Row['Name']), '/localization/locale/'.rawurlencode($Row['Locale'])); ?></td>
         <td class="PercentColumn"><?php echo PercentBox($Row['PercentCore'], '%s translated', $Row['PercentApprovedCore'], '%s approved'); ?></td>
         <td class="PercentColumn"><?php echo PercentBox($Row['PercentAdmin'], '%s translated', $Row['PercentApprovedAdmin'], '%s approved'); ?></td>
         <td class="PercentColumn"><?php echo PercentBox($Row['PercentAddon'], '%s translated', $Row['PercentApprovedAddon'], '%s approved'); ?></td>
      </tr>
      <?php endforeach; ?>
   </tbody>
</table>

<?php Gdn_Theme::AssetBegin('Panel'); ?>
<div class="Box">
   <h4><?php echo T('Hello'), ', ', Hello(); ?></h4>
   <p>
      <?php
         echo T('If you can speak another language then we built this section just for you.');
      ?>
   </p>
   <p>
      <?php
         echo T('Please consider joining the translation team for your language.', 'Please consider joining the translation team for your language to help us make Vanilla available for as many people as possible.');
      ?>
   </p>
</div>

<div class="Box">
<?php
echo '<h4>'.T("Don't See Your Language Here?").'</h4>';

echo '<p>'.FormatString(T('Just ask us to add it!', "Just ask us to add it in the <a href=\"{/categories/localization,url}\">Localization Forum</a>.")).'</p>';
?>
</div>

<?php Gdn_Theme::AssetEnd(); ?>