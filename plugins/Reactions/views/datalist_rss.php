<?php if (!defined('APPLICATION')) exit(); ?>
<description><?php echo htmlspecialchars($this->Data('Title')); ?></description>
<language><?php echo Gdn::Config('Garden.Locale', 'en-US'); ?></language>
<atom:link href="<?php echo htmlspecialchars(Url($this->SelfUrl, TRUE)); ?>" rel="self" type="application/rss+xml" />

<?php foreach ($this->Data('Data', array()) as $Row): ?>
   <item>
      <title><?php echo Gdn_Format::Text(GetValue('Name', $Row)); ?></title>
      <link><?php echo $Row['Url']; ?></link>
      <pubDate><?php echo date('r', Gdn_Format::ToTimeStamp($Row['DateInserted'])); ?></pubDate>
      <dc:creator><?php echo Gdn_Format::Text($Row['InsertName']); ?></dc:creator>
      <guid isPermaLink="false"><?php echo "{$Row['RecordID']}@{$Row['RecordType']}"; ?></guid>
      <description><![CDATA[<?php echo Gdn_Format::RssHtml($Row['Body'], $Row['Format']); ?>]]></description>
   </item>
<?php endforeach; ?>