<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

class RankModel extends Gdn_Model {
   public function __construct() {
      parent::__construct('Rank');
   }
   
   public function ApplyRank($User) {
      if (is_numeric($User)) {
         $User = Gdn::UserModel()->GetID($User, DATASET_TYPE_ARRAY);
      }
      $User = (array)$User;
      
      $CurrentRankID = GetValue('RankID', $User);
      $Result = array('CurrentRank' => $CurrentRankID ? self::Ranks($CurrentRankID) : NULL);
      
      $Ranks = self::Ranks();
      
      // Check the ranks backwards so we know which rank to apply.
      $Ranks = array_reverse($Ranks);
      foreach ($Ranks as $Rank) {
         if (self::TestRank($User, $Rank)) {
            $RankID = $Rank['RankID'];
            $Result['NewRank'] = $Rank;
            break;
         }
      }
      
      if (isset($RankID) && $RankID == $CurrentRankID)
         return $Result;
      
      // Apply the rank.
      $UserID = GetValue('UserID', $User);
      Gdn::UserModel()->SetField($UserID, 'RankID', $RankID);
      
      $Notify = $Rank['Level'] > 1;
      
      if (!isset($Result['NewRank']) || ($Result['CurrentRank'] && $Result['NewRank']['Level'] < $Result['CurrentRank']['Level']))
         $Notify = FALSE;
      
      if ($Notify) {
         $this->Notify($User, $Rank);
      }
      return $Result;
   }
   
   public function Notify($User, $Rank) {
      $UserID = GetValue('UserID', $User);
      $RankID = $Rank['RankID'];
      
      
      // Notify people of the rank.
      $Activity = array(
            'ActivityType' => 'Rank',
            'ActivityUserID' => $UserID,
            'NotifyUserID' => $UserID,
            'HeadlineFormat' => T('Ranks.NotificationFormat', 'Congratulations! You\'ve been promoted to {Data.Name,plaintext}.'),
            'Story' => GetValue('Body', $Rank),
            'RecordType' => 'Rank',
            'RecordID' => $RankID,
            'Route' => "/profile",
            'Emailed' => ActivityModel::SENT_PENDING,
            'Notified' => ActivityModel::SENT_PENDING,
            'Photo' => 'http://cdn.vanillaforums.com/images/ranks_100.png',
            'Data' => array('Name' => $Rank['Name'], 'Label' => $Rank['Label'])
      );

      $ActivityModel = new ActivityModel();
      $ActivityModel->Queue($Activity, FALSE, array('Force' => TRUE));

      // Notify everyone else of your badge.
      $Activity['NotifyUserID'] = ActivityModel::NOTIFY_PUBLIC;
      $Activity['HeadlineFormat'] = T('Ranks.ActivityFormat', '{ActivityUserID,user} {ActivityUserID,plural,was,were} promoted to {Data.Name,plaintext}.');
      $Activity['Emailed'] = ActivityModel::SENT_OK;
      $Activity['Popup'] = ActivityModel::SENT_OK;
      unset($Activity['Route']);
      $ActivityModel->Queue($Activity, FALSE, array('GroupBy' => array('ActivityTypeID', 'RecordID', 'RecordType')));

      $ActivityModel->SaveQueue();
   }
   
   public static function AbilitiesString($Rank) {
      $Abilities = GetValue('Abilities', $Rank);
      $Result = array();
      
      self::AbilityString($Abilities, 'DiscussionsAdd', 'Add Discussions', $Result);
      self::AbilityString($Abilities, 'CommentsAdd', 'Add Comments', $Result);
      self::AbilityString($Abilities, 'Verified', 'Verified', $Result);
      
      $V = GetValue('Format', $Abilities);
      if ($V) {
         $V = strtolower($V);
         if ($V == 'textex')
            $V = 'text, links, youtube';
         
         $Result[] = '<b>Post Format</b>: '.$V;
      }
      
      self::AbilityString($Abilities, 'ActivityLinks', 'Activity Links', $Result);
//      self::AbilityString($Abilities, 'CommentLinks', 'Discussion & Comment Links', $Result);
      
      self::AbilityString($Abilities, 'Titles', 'Titles', $Result);
      self::AbilityString($Abilities, 'Signatures', 'Signatures', $Result);
      self::AbilityString($Abilities, 'Polls', 'Polls', $Result);
      self::AbilityString($Abilities, 'MeAction', 'Me Actions', $Result);
      self::AbilityString($Abilities, 'Curation', 'Content Curation', $Result);
      
      $V = GetValue('EditContentTimeout', $Abilities, '');
      if ($V !== '') {
         $Options = self::ContentEditingOptions();
         $Result[] = '<b>'.T('Editing').'</b>: '.GetValue($V, $Options);
      }
      
      if (count($Result) == 0) {
         return '';
      } elseif (count($Result) == 1) {
         return array_pop($Result);
      } else {
         return '<ul BulletList><li>'.implode('</li><li>', $Result).'</li></ul>';
      }
   }
   
   public static function AbilityString($Abilities, $Value, $String, &$Result) {
      $V = GetValue($Value, $Abilities);
      
      if ($V === 'yes') {
         $Result[] = '<b>Add</b>: '.$String;
      } elseif ($V === 'no') {
         $Result[] = '<b>Remove</b>: '.$String;
      }
   }
   
   public static function ApplyAbilities() {
      $Session = Gdn::Session();
      if (!$Session->User)
         return;
      
      $RanksPlugin = Gdn::PluginManager()->GetPluginInstance('RanksPlugin');
      
      $Rank = self::Ranks(GetValue('RankID', $Session->User, FALSE));
      if (!$Rank)
         return;
      
      $Abilities = GetValue('Abilities', $Rank, array());
      
      // Post discussions.
      if ($V = GetValue('DiscussionsAdd', $Abilities)) {
         if ($V == 'no')
            $Session->SetPermission('Vanilla.Discussions.Add', array());
      }
      
      // Add comments.
      if ($V = GetValue('CommentsAdd', $Abilities)) {
         if ($V == 'no')
            $Session->SetPermission('Vanilla.Comments.Add', array());
      }
      
      // Verified.
      if ($V = GetValue('Verified', $Abilities)) {
         $Verified = array(
            'yes' => 1,
            'no'  => 0
         );
         $Verified = GetValue($V, $Verified, null);
         if (is_integer($Verified))
            $Session->User->Verified = $Verified;
      }
      
      // Post Format.
      if ($V = GetValue('Format', $Abilities)) {
         SaveToConfig(array(
            'Garden.InputFormatter' => $V,
            'Garden.InputFormatterBak' => C('Garden.InputFormatter'),
            'Garden.ForceInputFormatter' => TRUE),
            NULL, FALSE);
      }
      
      // Titles.
      if ($V = GetValue('Titles', $Abilities)) {
         SaveToConfig('Garden.Profile.Titles', $V == 'yes' ? TRUE : FALSE, FALSE);
      }
      
      // Signatures.
      if ($V = GetValue('Signatures', $Abilities)) {
         $Session->SetPermission('Plugins.Signatures.Edit', $V == 'yes' ? TRUE : FALSE);
      }
      
      // Polls.
      if ($V = GetValue('Polls', $Abilities)) {
         $Session->SetPermission('Plugins.Polls.Add', $V == 'yes' ? TRUE : FALSE);
      }
      
      // MeActions.
      if ($V = GetValue('MeAction', $Abilities)) {
         $Session->SetPermission('Vanilla.Comments.Me', $V == 'yes' ? TRUE : FALSE);
      }
      
      /// Content curation.
      if ($V = GetValue('Curation', $Abilities)) {
         $Session->SetPermission('Garden.Curation.Manage', $V == 'yes' ? TRUE : FALSE);
      }
      
      // Links.
      $RanksPlugin->ActivityLinks = GetValue('ActivityLinks', $Abilities);
      $RanksPlugin->CommentLinks = GetValue('CommentLinks', $Abilities);
      
      // Edit content timeout.
      if ($V = GetValue('EditContentTimeout', $Abilities)) {
         SaveToConfig('Garden.EditContentTimeout', $V, FALSE);
      }
   }
   
   public function Calculate(&$Data) {
      if (is_array($Data) && isset($Data[0])) {
         // Multiple badges
         foreach ($Data as &$B) {
            $this->_Calculate($B);
         }
      } elseif ($Data) {
         // One valid result
         $this->_Calculate($Data);
      }
   }
   
   public static function CriteriaString($Rank) {
      $Criteria = GetValue('Criteria', $Rank);
      $Result = array();
      
      if ($V = GetValue('Points', $Criteria)) {
         $Result[] = Plural($V, '%s point', '%s points');
      }
      
      if ($V = GetValue('Time', $Criteria)) {
         $Result[] = sprintf(T('member for %s'), $V);
      }
      
      if ($V = GetValue('CountPosts', $Criteria)) {
         $Result[] = Plural($V, '%s post', '%s posts');
      }
      
      if (isset($Criteria['Permission'])) {
         $Permissions = (array)$Criteria['Permission'];
         foreach ($Permissions as $Permission) {
            switch ($Permission) {
               case 'Garden.Moderation.Manage':
                  $Result[] = 'Must be a moderator';
                  break;
               case 'Garden.Settings.Manage':
                  $Result[] = 'Must be an administrator';
                  break;
            }
         }
      }
      
      if ($V = GetValue('Manual', $Criteria)) {
         $Result[] = T('Applied Manually');
      }
      
      if (sizeof($Result)) {
         if (count($Result) == 1)
            return array_pop($Result);
         else
            return '<ul BulletList><li>'.implode('</li><li>', $Result).'</li></ul>';
      } else {
         return '';
      }
   }
   
   protected function _Calculate(&$Data) {
      if (isset($Data['Attributes']) && !empty($Data['Attributes']))
         $Attributes = @unserialize($Data['Attributes']);
      else
         $Attributes = array();
      
      unset($Data['Attributes']);
      $Data = array_merge($Data, $Attributes);
   }
   
   public static function ContentEditingOptions() {
      static $Options = NULL;
      
      if (!isset($Options)) {
         $Options = array('' => T('default'),
                  '0' => T('Authors may never edit'),
               '350' => sprintf(T('Authors may edit for %s'), T('5 minutes')), 
               '900' => sprintf(T('Authors may edit for %s'), T('15 minutes')), 
               '3600' => sprintf(T('Authors may edit for %s'), T('1 hour')),
               '14400' => sprintf(T('Authors may edit for %s'), T('4 hours')),
               '86400' => sprintf(T('Authors may edit for %s'), T('1 day')),
            '604800' => sprintf(T('Authors may edit for %s'), T('1 week')),
            '2592000' => sprintf(T('Authors may edit for %s'), T('1 month')),
                  '-1' => T('Authors may always edit'));
      }
      
      return $Options;
   }
   
   public function GetWhere($Where = FALSE, $OrderFields = 'Level', $OrderDirection = 'asc', $Limit = FALSE, $Offset = FALSE) {
      $Result = parent::GetWhere($Where, $OrderFields, $OrderDirection, $Limit, $Offset);
      $this->Calculate($Result->ResultArray());
      return $Result;
   }
   
   protected static $_Ranks = NULL;
   
   public static function Ranks($RankID = NULL) {
      if (self::$_Ranks === NULL) {
         $M = new RankModel();
         $Ranks = $M->GetWhere()->ResultArray();
         $Ranks = Gdn_DataSet::Index($Ranks, array('RankID'));
         self::$_Ranks = $Ranks;
      }
      
      if (!is_null($RankID) && !is_bool($RankID))
         return GetValue($RankID, self::$_Ranks, NULL);
      else
         return self::$_Ranks;
   }
   
   public function Save($Data) {
      // Put the data into a format that's savible.
      $this->DefineSchema();
      $SchemaFields = $this->Schema->Fields();
      
      $SaveData = array();
      $Attributes = array();
      
      foreach ($Data as $Name => $Value) {
         if ($Name == 'Attributes')
            continue;
         if (isset($SchemaFields[$Name]))
            $SaveData[$Name] = $Value;
         else
            $Attributes[$Name] = $Value;
      }
      if (sizeof($Attributes))
         $SaveData['Attributes'] = $Attributes;
      
      
      // Grab the current rank.
      if (isset($SaveData['RankID'])) {
         $PrimaryKeyVal = $SaveData['RankID'];
         $CurrentRank = $this->SQL->GetWhere('Rank', array('RankID' => $PrimaryKeyVal))->FirstRow(DATASET_TYPE_ARRAY);
         if ($CurrentRank)
            $Insert = FALSE;
         else
            $Insert = TRUE;
      } else {
         $PrimaryKeyVal = FALSE;
         $Insert = TRUE;
      }
      
      // Validate the form posted values.
      if ($this->Validate($SaveData, $Insert) === TRUE) {
         $Fields = $this->Validation->ValidationFields();
         
         if ($Insert === FALSE) {
            $Fields = RemoveKeyFromArray($Fields, $this->PrimaryKey); // Don't try to update the primary key
            $this->Update($Fields, array($this->PrimaryKey => $PrimaryKeyVal));
         } else {
            $PrimaryKeyVal = $this->Insert($Fields);
         }
      } else {
         $PrimaryKeyVal = FALSE;
      }
      return $PrimaryKeyVal;
   }
   
   /**
    * Test whether or not a user is eligible for a rank.
    * 
    * @param array|object $User
    * @param array $Rank 
    */
   public static function TestRank($User, $Rank) {
      if (!isset($Rank['Criteria']) || !is_array($Rank['Criteria'])) {
         return TRUE;
      }
      
      $Criteria = $Rank['Criteria'];
      // All criteria must apply so return false if any of the criteria doesn't match.
      $UserPoints = GetValue('Points', $User);

      if (isset($Criteria['Points'])) {
         $PointsCriteria = $Criteria['Points'];
         
         if ($PointsCriteria >= 0 && $UserPoints < $PointsCriteria)
            return FALSE;
         elseif ($PointsCriteria < 0 && $UserPoints > $PointsCriteria)
            return FALSE;
      }
      
      if (isset($Criteria['Time'])) {
         $TimeFirstVisit = Gdn_Format::ToTimestamp(GetValue('DateFirstVisit', $User));
         $TimeCriteria = strtotime($Criteria['Time'], 0);
         
         if ($TimeCriteria && ($TimeFirstVisit + $TimeCriteria > time()))
            return FALSE;
      }
      
      if (isset($Criteria['CountPosts'])) {
         $CountPosts = GetValue('CountDiscussions', $User, 0) + GetValue('CountComments', $User, 0);
         if ($CountPosts < $Criteria['CountPosts'])
            return FALSE;
      }

      if (isset($Criteria['Permission'])) {
         $Permissions = (array)$Criteria['Permission'];
         foreach ($Permissions as $Perm) {
            if (!Gdn::UserModel()->CheckPermission($User, $Perm))
               return FALSE;
         }
      }
      
      if ($V = GetValue('Manual', $Criteria)) {
         if (GetValue('RankID', $User) != $Rank['RankID'])
            return FALSE;
      }
      
      return TRUE;
   }
}