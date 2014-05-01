<?php if (!defined('APPLICATION')) exit(); 
//Creating an instance of FeedWriter class. 
//The constant ATOM is passed to mention the version
$Feed = new FeedWriter(ATOM);
$Feed->setTitle(C('Garden.Title', 'Vanilla Activity Feed'));
$Feed->setLink(Url('activity/googlesocialactivitydata', TRUE));

//For other channel elements, use setChannelElement() function
// $TestFeed->setChannelElement('updated', date(DATE_ATOM , time()));
// $TestFeed->setChannelElement('author', array('name'=>'Anis uddin Ahmad'));

foreach ($this->Data('Activities') as $Activity) {
   $Activity = (object)$Activity;
   // If this was a status update or a wall comment, don't bother with activity strings
   $ActivityType = explode(' ', $Activity->ActivityType); // Make sure you strip out any extra css classes munged in here
   $ActivityType = $ActivityType[0];
   $Author = UserBuilder($Activity, 'Activity');
   $Format = GetValue('Format', $Activity);
   $Title = '';
   $Excerpt = $Activity->Story;
   if ($Format)
      $Excerpt = Gdn_Format::To($Excerpt, $Format);
   
   if (!in_array($ActivityType, array('WallComment', 'WallPost', 'AboutUpdate'))) {
      $Title = GetValue('Headline', $Activity);
   } else if ($ActivityType == 'WallPost') {
      $RegardingUser = UserBuilder($Activity, 'Regarding');
      $PhotoAnchor = UserPhoto($RegardingUser);
      $Title = UserAnchor($RegardingUser, 'Name').' &rarr; '.UserAnchor($Author, 'Name');
      if (!$Format)
         $Excerpt = Gdn_Format::Display($Excerpt);
   } else {
      $Title = UserAnchor($Author, 'Name');
      if (!$Format)
         $Excerpt = Gdn_Format::Display($Excerpt);
   }
   
   $Item = $Feed->createNewItem();
   $Item->setTitle(Gdn_Format::PlainText($Title));
   $Item->setLink(Url('activity/item/'.$Activity->ActivityID, TRUE));
   $Item->setDate($Activity->DateUpdated);
   $Item->setDescription(Wrap($Title, 'strong').Wrap($Excerpt, 'p'));
   $Feed->addItem($Item);
}
$Feed->generateFeed();