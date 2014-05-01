<?php if (!defined('APPLICATION')) exit(); 
header("Content-type: text/xml");
$Url = Url('activity/googlesocialcommentdata', TRUE);
echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
echo '<feed xmlns="http://www.w3.org/2005/Atom">' . PHP_EOL;;
echo Wrap(C('Garden.Title', 'Vanilla Comment Feed'), 'title').PHP_EOL;
echo '<link href="'.$Url.'"/>'.PHP_EOL;
echo Wrap(GoogleSocialDataHubPlugin::uuid($Url,'urn:uuid:'), 'id').PHP_EOL;
foreach ($this->Data('Comments') as $Comment) {
   $Comment = (object)$Comment;
   echo '<entry>'.PHP_EOL;
      echo Wrap(Gdn_Format::PlainText($Comment->InsertName).' commented on "'.Gdn_Format::PlainText($Comment->DiscussionName).'".', 'title').PHP_EOL;
      echo '<link href="'.Url('discussion/comment/'.$Comment->CommentID.'/#Comment_'.$Comment->CommentID, TRUE).'"/>'.PHP_EOL;
      echo Wrap(date(DATE_ATOM, Gdn_Format::ToTimestamp($Comment->DateInserted)), 'updated').PHP_EOL;
      // echo Wrap($Comment->DateInserted, 'updated');
      echo '<author>'.PHP_EOL;
         echo Wrap(str_replace(array('http://', 'https://'), array('', ''), Gdn::Request()->Domain()).':userid:'.$Comment->InsertUserID, 'id').PHP_EOL;
         echo Wrap($Comment->InsertName, 'name').PHP_EOL;
         echo Wrap(Url(UserUrl($Comment, 'Insert'), TRUE), 'uri').PHP_EOL;
      echo '</author>'.PHP_EOL;
      echo '<summary type="html"><![CDATA[';
         echo Gdn_Format::To($Comment->Body, $Comment->Format);
      echo ']]></summary>'.PHP_EOL;
   echo '</entry>'.PHP_EOL;
}
echo '</feed>';