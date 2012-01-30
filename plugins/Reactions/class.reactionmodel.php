<?php if (!defined('APPLICATION')) exit();

class ReactionModel {
   /// Properties ///
   
   public static $ReactionTypes = NULL;
   
   /**
    * @var Gdn_SQL 
    */
   public $SQL;
   
   /// Methods ///
   
   public function __construct() {
      $this->SQL = Gdn::SQL();
   }
   
   public function DefineReactionType($Data) {
      $UrlCode = $Data['UrlCode'];
      
      // Grab the tag.
      $Row = Gdn::SQL()->GetWhere('Tag', array('Name' => $UrlCode))->FirstRow(DATASET_TYPE_ARRAY);
      
      if (!$Row) {
         $TagID = Gdn::SQL()->Insert('Tag', array(
             'Name' => $UrlCode,
             'Type' => 'Reaction',
             'InsertUserID' => Gdn::Session()->UserID,
             'DateInserted' => Gdn_Format::ToDateTime())
         );
      } else {
         $TagID = $Row['TagID'];
         if ($Row['Type'] != 'Reaction') {
            Gdn::SQL()->Put('Tag', array('Name' => $UrlCode, 'Type' => 'Reaction'), array('TagID' => $TagID));
         }
      }
      $Data['TagID'] = $TagID;
      
      $Row = array();
      $Columns = array('UrlCode', 'Name', 'Description', 'TagID');
      foreach ($Columns as $Column) {
         if (isset($Data[$Column])) {
            $Row[$Column] = $Data[$Column];
            unset($Data[$Column]);
         }
      }
      
      if (!empty($Data)) {
         $Row['Attributes'] = serialize($Data);
      }
      
      Gdn::SQL()->Replace('ReactionType', $Row, array('UrlCode' => $UrlCode), TRUE);
      
      Gdn::Cache()->Remove('ReactionTypes');
      
      return $Data;
   }
   
   public function GetRow($Type, $ID, $Operation) {
      switch ($Type) {
         case 'Comment':
            $Model = new CommentModel();
            $Row = $Model->GetID($ID, DATASET_TYPE_ARRAY);
            break;
         case 'Discussion':
            $Model = new DiscussionModel();
            $Row = $Model->GetID($ID);
            break;
         default:
            throw NotFoundException(ucfirst($Type));
      }
      
      $Log = NULL;
      if (!$Row) {
         // The row may have been logged so try and grab it.
         $LogModel = new LogModel();
         $Log = $LogModel->GetWhere(array('RecordType' => $Type, 'RecordID' => $ID, 'Operation' => $Operation));
         
         if (count($Log) == 0)
            throw NotFoundException($Type);
         $Log = $Log[0];
         $Row = $Log['Data'];
      }
      $Row = (array)$Row;
      
      // Make sure the attributes are in the row and unserialized.
      $Attributes = GetValue('Attributes', $Row, array());
      if (is_string($Attributes))
         $Attributes = @unserialize($Attributes);
      if (!is_array($Attributes))
         $Attributes = array();
      
      $Row['Attributes'] = $Attributes;
      return array($Row, $Model, $Log);
   }
   
   public function ToggleUserTag(&$Data, &$Record) {
      $Inc = GetValue('Total', $Data, 1);
      
      TouchValue('Total', $Data, $Inc);
      TouchValue('DateInserted', $Data, Gdn_Format::ToDateTime());
      
      // See if there is already a user tag.
      $Where = ArrayTranslate($Data, array('RecordType', 'RecordID', 'UserID'));
      
      $UserTags = $this->SQL->GetWhere('UserTag', $Where)->ResultArray();
      $UserTags = Gdn_DataSet::Index($UserTags, array('TagID'));
      $Insert = TRUE;
      
      if (isset($UserTags[$Data['TagID']])) {
         // The user is toggling a tag they've already done.
         $Insert = FALSE;
            
         $Inc = -$UserTags[$Data['TagID']]['Total'];
         $Data['Total'] = $Inc;
      }
      
      $RecordType = $Data['RecordType'];
      
      // Delete all of the tags.
      if (count($UserTags) > 0) {
         $DeleteWhere = $Where;
         $DeleteWhere['TagID'] = array_keys($UserTags);
         $this->SQL->Delete('UserTag', $DeleteWhere);
      }
      
      if ($Insert) {
         // Insert the tag.
         $this->SQL->Options('Ignore', TRUE)->Insert('UserTag', $Data);
         
         // We add the row to the usertags set, but with a negative total.
         $UserTags[$Data['TagID']] = $Data;
         $UserTags[$Data['TagID']]['Total'] *= -1;
      }
      
      // Now we need to increment the totals.
      $Px = $this->SQL->Database->DatabasePrefix;
      $Sql = "insert {$Px}UserTag (RecordType, RecordID, TagID, UserID, DateInserted, Total)
         values (:RecordType, :RecordID, :TagID, :UserID, :DateInserted, :Total)
         on duplicate key update Total = Total + :Total2";
      
      foreach ($UserTags as $Row) {
         $Args = ArrayTranslate($Row, array(
             'RecordType' => ':RecordType', 
             'RecordID' => ':RecordID', 
             'TagID' => ':TagID', 
             'UserID' => ':UserID', 
             'DateInserted' => ':DateInserted'));
         $Args[':Total'] = -$Row['Total'];
         $Args[':Total2'] = $Args[':Total'];

         // Increment the record total.
         $Args[':UserID'] = 0;
         $this->SQL->Database->Query($Sql, $Args);

         // Increment the user total.
         $Args[':RecordType'] = 'User';
         $Args[':RecordID'] = $Record['InsertUserID'];
         $this->SQL->Database->Query($Sql, $Args);
      }
      
      // Recalculate the counts for the record.
      $TotalTags = $this->SQL->GetWhere('UserTag', array('RecordType' => $Data['RecordType'], 'RecordID' => $Data['RecordID'], 'UserID' => 0))->ResultArray();
      $TotalTags = Gdn_DataSet::Index($TotalTags, array('TagID'));
      $ReactionTypes = self::ReactionTypes();
      $React = array();
      $Diffs = array();
      $Set = array();
      foreach ($ReactionTypes as $UrlCode => $Type) {
         $TagID = $Type['TagID'];
         if (isset($TotalTags[$TagID])) {
            $React[$Type['UrlCode']] = $TotalTags[$TagID]['Total'];
            
            if ($Column = GetValue('IncrementColumn', $Type)) {
               // This reaction type also increments a column so do that too.
               TouchValue($Column, $Set, 0);
               $Set[$Column] += $TotalTags[$TagID]['Total'];
            }
         }
         
         if (GetValueR("Attributes.React.{$Type['UrlCode']}", $Record) != GetValue($Type['UrlCode'], $React)) {
            $Diffs[] = $Type['UrlCode'];
         }
      }
      
      // Send back the current scores.
      foreach ($Set as $Column => $Value) {
         Gdn::Controller()->JsonTarget(
               "#{$RecordType}_{$Data['RecordID']} .Column-".$Column, 
               $Value,
               'Html');
      }
      
      $Record['Attributes']['React'] = $React;
      $Set['Attributes'] = serialize($Record['Attributes']);
      $this->SQL->Put($Data['RecordType'],
         $Set,
         array($Data['RecordType'].'ID' => $Data['RecordID']));
      
      // Generate the new button for the reaction.
      Gdn::Controller()->SetData('Diffs', $Diffs);
      if (function_exists('ReactionButton')) {
         foreach ($Diffs as $UrlCode) {
            $Button = ReactionButton($Record, $UrlCode);
            Gdn::Controller()->JsonTarget(
               "#{$RecordType}_{$Data['RecordID']} .ReactButton-".$UrlCode, 
               $Button,
               'ReplaceWith');
         }
      }
   }
   
   /**
    *
    * @param string $RecordType
    * @param int $ID
    * @param string $Reaction 
    */
   public function React($RecordType, $ID, $Reaction) {
      $IsModerator = Gdn::Session()->CheckPermission('Garden.Moderation.Manage');
      
      $Undo = FALSE;
      if (StringBeginsWith($Reaction, 'Undo-', TRUE)) {
         $Undo = TRUE;
         $Reaction = StringBeginsWith($Reaction, 'Undo-', TRUE, TRUE);
      }
      $UserID = Gdn::Session()->UserID;
      $RecordType = ucfirst($RecordType);
      $Reaction = strtolower($Reaction);
      $ReactionType = self::ReactionTypes($Reaction);
      if (!$ReactionType)
         throw NotFoundException($Reaction);
      
      $LogOperation = GetValue('Log', $ReactionType);
      
      list($Row, $Model, $Log) = $this->GetRow($RecordType, $ID, $LogOperation);
      
      if (!$IsModerator && $Row['InsertUserID'] == $UserID) {
         throw new Gdn_UserException(T("You can't react to your own post."));
      }
      
      // Figure out the increment.
      if ($IsModerator) {
         $Inc = GetValue('ModeratorInc', $ReactionType, 1);
      } else {
         $Inc = 1;
      }
      
      // Save the user Tag.
      $Data = array(
          'RecordType' => $RecordType,
          'RecordID' => $ID,
          'TagID' => $ReactionType['TagID'],
          'UserID' => $UserID,
          'Total' => $Inc
          );
      $this->ToggleUserTag($Data, $Row);
      
      $Message = array(T(GetValue('InformMessage', $ReactionType, 'Thanks for the reaction!')), 'Dismissable AutoDismiss');
      
      // Now deciede whether we need to log or delete the record.
      $Score = GetValueR('Attributes.React.'.$ReactionType['UrlCode'], $Row);
      $LogThreshold = GetValue('LogThreshold', $ReactionType, 10000000);
      $RemoveThreshold = GetValue('RemoveThreshold', $ReactionType, 10000000);
      
      if (!GetValueR('Attributes.RestoreUserID', $Row)) {
         // We are only going to remove stuff if the record has not been verified.
         $Log = GetValue('Log', $ReactionType, 'Moderation');
         
         $LogOptions = array('GroupBy' => array('RecordID'));
         $UndoButton = '';
         
         if ($Score >= min($LogThreshold, $RemoveThreshold)) {
            // Get all of the userIDs that flagged this.
            $OtherUserData = $this->SQL->GetWhere('UserTag', array('RecordType' => $RecordType, 'RecordID' => $ID, 'TagID' => $ReactionType['TagID']))->ResultArray();
            $OtherUserIDs = array();
            foreach ($OtherUserData as $Row) {
               if ($Row['UserID'] == $UserID || !$Row['UserID'])
                  continue;
               $OtherUserIDs[] = $Row['UserID'];
            }
            $LogOptions['OtherUserIDs'] = $OtherUserIDs;
         }
         
         
         if ($Score >= $RemoveThreshold) {
            // Remove the record to the log.
            $Model->Delete($ID, array('Log' => $Log, 'LogOptions' => $LogOptions));
            $Message = array(
            sprintf(T('The %s has been removed for moderation.'), T($RecordType))
               .' '.$UndoButton,
               array('CssClass' => 'Dismissable', 'id' => 'mod')
            );
            // Send back a command to remove the row in the browser.
            if ($RecordType == 'Discussion') {
               Gdn::Controller()->JsonTarget('.ItemDiscussion', '', 'SlideUp');
               Gdn::Controller()->JsonTarget('#Content .Comments', '', 'SlideUp');
               Gdn::Controller()->JsonTarget('.CommentForm', '', 'SlideUp');
            } else {
               Gdn::Controller()->JsonTarget("#{$RecordType}_$ID", '', 'SlideUp');
            }
         } elseif ($Score >= $LogThreshold) {
            LogModel::Insert($Log, $RecordType, $Row, $LogOptions);
            $Message = array(
            sprintf(T('The %s has been flagged for moderation.'), T($RecordType))
               .' '.$UndoButton,
               array('CssClass' => 'Dismissable', 'id' => 'mod')
            );
         }
      } else {
         if ($Score >= min($LogThreshold, $RemoveThreshold)) {
            $RestoreUser = Gdn::UserModel()->GetID(GetValueR('Attributes.RestoreUserID', $Row));
            $DateRestored = GetValueR('Attributes.DateRestored', $Row);
            
            // The post would have been logged, but since it has been restored we won't do that again.
            $Message = array(
            sprintf(T('The %s was already approved by %s on %s.'), T($RecordType), UserAnchor($RestoreUser), Gdn_Format::DateFull($DateRestored)),
               array('CssClass' => 'Dismissable', 'id' => 'mod')
            );
         }
      }
      
      if ($Message)
         Gdn::Controller()->InformMessage($Message[0], $Message[1]);
      
//      if ($Undo)
//         $UndoButton = $this->Button(T('Report '.ucfirst($Reaction), ucfirst($Reaction)), $Reaction, $RecordType, $ID, FALSE);
//      else
//         $UndoButton = $this->Button(T('Undo '.ucfirst($Reaction), 'Undo'), 'undo-'.$Reaction, $RecordType, $ID, FALSE);
//      
//      
//      $Targets = array();
//      if ($Reaction == 'like') 
//         $MessageBody = sprintf('You liked the %s. Thanks!', strtolower($RecordType));
//      else
//         $MessageBody = sprintf('The %s has been flagged. Thanks!', strtolower($RecordType));
//      
//      $MessageBody = T($MessageBody);
//      $Message = array('<span class="InformSprite Flag"></span> '.$MessageBody, array('CssClass' => 'Dismissable AutoDismiss HasSprite', 'id' => 'mod'));
//      
//      if ($Undo) {
//         if ($Log) {
//            // The row was logged and now must be restored.
//            $LogModel = new LogModel();
//            $Log['Data'] = $Row;
//            $LogModel->Restore($Log);
//
//            if ($RecordType == 'Comment') {
//               $Targets[] = array('Target' => "#{$RecordType}_$ID", 'Type' => 'SlideDown');
//            } else {
//               // Send back a refresh command. It's a bit too complicated to reveal everything.
//               $Sender->RedirectUrl = Url("/discussion/{$Row['DiscussionID']}/".Gdn_Format::Url($Row['Name']));
//            }
//         } else {
//            // The row just needs to be updated.
//            $Model->SetProperty($ID,
//               array($Column => $Row[$Column], 'Attributes' => serialize($Row['Attributes'])),
//               ''
//            );
//         }
//         $Message[0] = '';
//      } else {
//         $LogOptions = array('GroupBy' => array('RecordID'));
//         // Get the User IDs that marked as spam.
//         $OtherUserIDs = array();
//         
//         foreach ($UserIDs as $UserID => $Val) {
//            if ($Val == $Abbrev && $UserID != Gdn::Session()->UserID)
//               $OtherUserIDs[] = $UserID;
//         }
//         $LogOptions['OtherUserIDs'] = $OtherUserIDs;
//         
//         if (RemoveThreshold && $Value >= RemoveThreshold) {
//            // We still need to update the row before deleting to get the right values in there.
//            $Model->SetProperty($ID,
//               array($Column => $Row[$Column], 'Attributes' => serialize($Row['Attributes'])),
//               ''
//            );
//            
//            // The row needs to be deleted.
//            $Model->Delete($ID, array('Log' => $LogOperation, 'LogOptions' => $LogOptions));
//            $Message = array(
//            sprintf(T('The %s has been removed for moderation.'), T($RecordType))
//               .' '.$UndoButton,
//               array('CssClass' => 'Dismissable', 'id' => 'mod')
//            );
//            // Send back a command to remove the row in the browser.
//            if ($RecordType == 'Discussion') {
//               $Targets[] = array('Target' => 'ul.Discussion', 'Type' => 'SlideUp');
//               $Targets[] = array('Target' => '.CommentForm', 'Type' => 'SlideUp');
//            } else
//               $Targets[] = array('Target' => "#{$RecordType}_$ID", 'Type' => 'SlideUp');
//         } elseif ($LogThreshold && $Value >= $LogThreshold) {
//            // The row needs to be logged and updated.
//            $Model->SetProperty($ID,
//               array($Column => $Row[$Column], 'Attributes' => serialize($Row['Attributes'])),
//               ''
//            );
//            
//            LogModel::Insert($LogOperation, $RecordType, $Row, $LogOptions);
//         } else {
//            // The row needs to just be updated.
//            $Model->SetProperty($ID,
//               array($Column => $Row[$Column], 'Attributes' => serialize($Row['Attributes'])),
//               ''
//            );
//         }
//      }
//      
//      // Send back a button to undo/redo the operation.
//      $Targets[] = array('Target' => "#{$RecordType}_$ID .Mod-".ucfirst($Reaction), 'Type' => 'Html', 'Data' => $UndoButton);
//         
//      // Send back the likes.
//      $Targets[] = array('Target' => "#{$RecordType}_$ID .Mod-Likes", 'Type' => 'Html', 'Data' => $this->Likes($Row, FALSE));
//      $Sender->InformMessage($Message[0], $Message[1]);
   }
   
   public static function ReactionTypes($UrlCode = NULL) {
      if (self::$ReactionTypes === NULL) {
         // Check the cache first.
         $ReactionTypes = Gdn::Cache()->Get('ReactionTypes');
         
         if ($ReactionTypes === Gdn_Cache::CACHEOP_FAILURE) {
            $ReactionTypes = Gdn::SQL()->Get('ReactionType')->ResultArray();
            foreach ($ReactionTypes as $Type) {
               $Row = $Type;
               $Attributes = @unserialize($Row['Attributes']);
               unset($Row['Attributes']);
               if (is_array($Attributes)) {
                  foreach ($Attributes as $Name => $Value) {
                     $Row[$Name] = $Value;
                  }
               }

               self::$ReactionTypes[strtolower($Row['UrlCode'])] = $Row;
            }
            Gdn::Cache()->Store('ReactionTypes', $ReactionTypes);
         } else {
            self::$ReactionTypes = $ReactionTypes;
         }
      }
      
      if ($UrlCode) {
         return self::$ReactionTypes[strtolower($UrlCode)];
      }
      
      return self::$ReactionTypes;
   }
}