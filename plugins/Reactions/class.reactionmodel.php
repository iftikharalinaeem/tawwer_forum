<?php if (!defined('APPLICATION')) exit();

class ReactionModel {
   /// Constants ///
   const USERID_SUM = 0;
   const USERID_OTHER = -1;
   
   /// Properties ///
   
   public static $ReactionTypes = NULL;
   public static $TagIDs = NULL;
   
   /**
    * @var Gdn_SQL 
    */
   public $SQL;
   
   /// Methods ///
   
   public function __construct() {
      $this->SQL = Gdn::SQL();
   }
   
   public function ConvertVanillaLabs() {
      $RecordTypes = array('Discussion', 'Comment');
      $Columns = array('Likes' => 'Awesome', 'Spam' => 'Spam', 'Abuse' => 'Abuse');
      $ReactionTypes = self::ReactionTypes();
      $Convert = array('L' => 'awesome', 'S' => 'spam', 'A' => 'abuse');
      
      $Count = 0;
      $RecordCount = 0;
      
      foreach ($RecordTypes as $RecordType) {
         foreach ($Columns as $Column => $ReactionCode) {
            $Data = $this->SQL
               ->Select()
               ->From($RecordType)
               ->Where($Column.'<>', 0)
               ->Get()->ResultArray();
            
            foreach ($Data as $Row) {
               $RecordCount++;
               
               $RecordID = $Row[$RecordType.'ID'];
               $Attributes = @unserialize($Row['Attributes']);
               if (!is_array($Attributes))
                  $Attributes = array();
               $ModUserIDs = GetValue('ModUserIDs', $Attributes);
               
               if (is_array($ModUserIDs)) {
                  foreach ($ModUserIDs as $UserID => $Code) {
                     if (!isset($Convert[$Code])) {
                        continue;
                     }
                     $UrlCode = $Convert[$Code];
                     $TagID = $ReactionTypes[$UrlCode]['TagID'];
                   
                     // Insert the tag.
                     $this->SQL->Options('Ignore', TRUE)
                        ->Insert('UserTag', array(
                            'RecordType' => $RecordType,
                            'RecordID' => $RecordID,
                            'TagID' => $TagID,
                            'UserID' => $UserID,
                            'DateInserted' => Gdn_Format::ToDateTime(),
                            'Total' => 1));
                     
                     $Count++;
                  }
                  // Now that all of the tags are processed we want to clear the labs information.
                  $Set = array_fill_keys(array_keys($Columns), 0);
                  unset($Attributes['ModUserIDs']);
                  if (empty($Attributes))
                     $Set['Attributes'] = NULL;
                  else
                     $Set['Attributes'] = serialize($Attributes);
                  $this->SQL->Put($RecordType, $Set, array($RecordType.'ID' => $RecordID));
               }
               unset($Attributes['ModUserIDs']);
            }
         }
      }
      
      $Result = array('CountReactions' => $Count, 'CountRecords' => $RecordCount);
      return $Result;
   }
   
   public function DefineTag($Name, $Type, $OldName = FALSE) {
      $Row = Gdn::SQL()->GetWhere('Tag', array('Name' => $Name))->FirstRow(DATASET_TYPE_ARRAY);
      
      if (!$Row && $OldName) {
         $Row = Gdn::SQL()->GetWhere('Tag', array('Name' => $OldName))->FirstRow(DATASET_TYPE_ARRAY);
      }
      
      if (!$Row) {
         $TagID = Gdn::SQL()->Insert('Tag', array(
             'Name' => $Name,
             'Type' => 'Reaction',
             'InsertUserID' => Gdn::Session()->UserID,
             'DateInserted' => Gdn_Format::ToDateTime())
         );
      } else {
         $TagID = $Row['TagID'];
         if ($Row['Type'] != $Type || $Row['Name'] != $Name) {
            Gdn::SQL()->Put('Tag', array(
                'Name' => $Name, 
                'Type' => $Type
                ), array('TagID' => $TagID));
         }
      }
      return $TagID;
   }
   
   public function DefineReactionType($Data, $OldCode = FALSE) {
      $UrlCode = $Data['UrlCode'];
      
      // Grab the tag.
      $TagID = $this->DefineTag($Data['UrlCode'], 'Reaction', $OldCode);
      $Data['TagID'] = $TagID;
      
      $Row = array();
      $Columns = array('UrlCode', 'Name', 'Description', 'Sort', 'Class', 'TagID', 'Active');
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
   
   public static function GetReactionTypes($Where = array()) {
      $Types = self::ReactionTypes();
      $Result = array();
      foreach ($Types as $Index => $Type) {
         if (self::Filter($Type, $Where))
            $Result[$Index] = $Type;
      }
      return $Result;
   }
   
   public static function Filter($Row, $Where) {
      foreach ($Where as $Column => $Value) {
         if (!isset($Row[$Column]) && $Value)
            return FALSE;
         
         $RowValue = $Row[$Column];
         if (is_array($Value)) {
            if (!in_array($RowValue, $Value))
               return FALSE;
         } else {
            if ($RowValue != $Value)
               return FALSE;
         }
      }
      return TRUE;
   }
   
   public static function FromTagID($TagID) {
      if (self::$TagIDs === NULL) {
         $Types = self::ReactionTypes();
//         decho($Types, 'Types');
         self::$TagIDs = Gdn_DataSet::Index($Types, array('TagID'));
         
      }
//      decho(self::$TagIDs, 'TagIDs');
      return GetValue($TagID, self::$TagIDs);
   }
   
   public function GetRecordsWhere($Where, $OrderFields = '', $OrderDirection = '', $Limit = 30, $Offset = 0) {
      // Grab the user tags.
      $UserTags = $this->SQL
         ->Limit($Limit, $Offset)
         ->GetWhere('UserTag', $Where, $OrderFields, $OrderDirection)->ResultArray();
      self::JoinRecords($UserTags);
      
      return $UserTags;
   }
   
   public function GetRow($Type, $ID, $Operation) {
      $AttrColumn = 'Attributes';
      
      switch ($Type) {
         case 'Comment':
            $Model = new CommentModel();
            $Row = $Model->GetID($ID, DATASET_TYPE_ARRAY);
            break;
         case 'Discussion':
            $Model = new DiscussionModel();
            $Row = $Model->GetID($ID);
            break;
         case 'Activity':
            $Model = new ActivityModel();
            $Row = $Model->GetID($ID, DATASET_TYPE_ARRAY);
            $AttrColumn = 'Data';
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
      $Attributes = GetValue($AttrColumn, $Row, array());
      if (is_string($Attributes))
         $Attributes = @unserialize($Attributes);
      if (!is_array($Attributes))
         $Attributes = array();
      
      $Row[$AttrColumn] = $Attributes;
      return array($Row, $Model, $Log);
   }
   
   public function JoinUserTags(&$Data, $RecordType = FALSE) {
      if (!$Data)
         return;
      
      $IDs = array();
      $UserIDs = array();
      $PK = $RecordType.'ID';
      
      if (is_a($Data, 'stdClass') || (is_array($Data) && !isset($Data[0]))) {
         $Data2 = array($Data);
      } else {
         $Data2 =& $Data;
      }
      
//      decho($Data);
      
      foreach ($Data2 as $Row) {
         if (!$RecordType)
            $RT = GetValue('RecordType', $Row);
         else
            $RT = $RecordType;
         
         $ID = GetValue($RT.'ID', $Row);
         
         if ($ID)
            $IDs[$RT][$ID] = 1;
      }
//      decho($IDs);
      
      $TagsData = array();
      foreach ($IDs as $RT => $In) {
         $TagsData[$RT] = $this->SQL
            ->Select('RecordID')
            ->Select('UserID')
            ->Select('TagID')
            ->Select('DateInserted')
            ->From('UserTag')
            ->Where('RecordType', $RT)
            ->WhereIn('RecordID', array_keys($In))
            ->OrderBy('DateInserted')
            ->Get()->ResultArray();
      }
      
      $Tags = array();
      foreach($TagsData as $RT => $Rows) {
         foreach ($Rows as $Row) {
            $UserIDs[$Row['UserID']] = 1;
            $Tags[$RT.'-'.$Row['RecordID']][] = $Row;
         }
      }
      
//      decho($Tags, 'Tags');
//      die();
      
      // Join the tags.
      foreach ($Data2 as &$Row) {
         if ($RecordType)
            $RT = $RecordType;
         else
            $RT = GetValue('RecordType', $Row);
         if (!$RT)
            $RT = 'RecordType';
         $PK = $RT.'ID';
         $ID = GetValue($PK, $Row);
         
         if ($ID)
            $TagRow = GetValue($RT.'-'.$ID, $Tags, array());
         else
            $TagRow = array();
         
         SetValue('UserTags', $Row, $TagRow);
      }
   }
   
   public static function JoinRecords(&$Data) {
      $IDs = array();
      $AllowedCats = DiscussionModel::CategoryPermissions();
      
      if ($AllowedCats === FALSE) {
         // This user does not have permission to view anything.
         $Data = array();
         return;
      }
      
      // Gather all of the ids to fetch.
      foreach ($Data as &$Row) {
         $RecordType = StringEndsWith($Row['RecordType'], '-Total', TRUE, TRUE);
         $Row['RecordType'] = $RecordType;
         $ID = $Row['RecordID'];
         $IDs[$RecordType][$ID] = $ID;
      }
      
      // Fetch all of the data in turn.
      $JoinData = array();
      foreach ($IDs as $RecordType => $RecordIDs) {
         if ($RecordType == 'Comment') {
            Gdn::SQL()->Select('d.Name, d.CategoryID')->Join('Discussion d', 'd.DiscussionID = r.DiscussionID');
         }
         
         $Rows = Gdn::SQL()->Select('r.*')->WhereIn($RecordType.'ID', array_values($RecordIDs))->Get($RecordType. ' r')->ResultArray();
         $JoinData[$RecordType] = Gdn_DataSet::Index($Rows, array($RecordType.'ID'));
      }
      
      // Join the rows.
      $Unset = array();
      foreach ($Data as $Index => &$Row) {
         $RecordType = $Row['RecordType'];
         $ID = $Row['RecordID'];
         
         if (!isset($JoinData[$RecordType][$ID])) {
            $Unset[] = $Index;
            continue; // orphaned?
         }
         
         $Record = $JoinData[$RecordType][$ID];
         
         if ($AllowedCats !== TRUE) {
            // Check to see if the user has permission to view this record.
            $CategoryID = GetValue('CategoryID', $Record, -1);
            if (!in_array($CategoryID, $AllowedCats)) {
               $Unset[] = $Index;
               continue;
            }
         }
         
         $Row = array_merge($Row, $Record);
         
         switch ($RecordType) {
            case 'Discussion':
               $Url = DiscussionUrl($Row, '', '#latest');
               break;
            case 'Comment':
               $Url = Url("/discussion/comment/$ID");
               break;
            default:
               $Url = '';
         }
         $Row['Url'] = $Url;
      }
      
      foreach ($Unset as $Index) {
         unset($Data[$Index]);
      }
      
      // Join the users.
      Gdn::UserModel()->JoinUsers($Data, array('InsertUserID'));
      
      if (!empty($Unset))
         $Data = array_values($Data);
   }
   
   /**
    *
    * @param type $Data
    * @param array $Record
    * @param Gdn_Model $Model 
    */
   public function ToggleUserTag(&$Data, &$Record, $Model) {
      $Inc = GetValue('Total', $Data, 1);
      
      TouchValue('Total', $Data, $Inc);
      TouchValue('DateInserted', $Data, Gdn_Format::ToDateTime());
      $ReactionTypes = self::ReactionTypes();
      $ReactionTypes = Gdn_DataSet::Index($ReactionTypes, array('TagID'));
      
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
      $AttrColumn = $RecordType == 'Activity' ? 'Data' : 'Attributes';
      
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
      
      
      $Points = 0;
      
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
         $Args[':RecordType'] = $RecordType.'-Total';
         $Args[':UserID'] = $Record['InsertUserID'];
         $this->SQL->Database->Query($Sql, $Args);

         // Increment the user total.
         $Args[':RecordType'] = 'User';
         $Args[':RecordID'] = $Record['InsertUserID'];
         $Args[':UserID'] = self::USERID_OTHER;
         $this->SQL->Database->Query($Sql, $Args);
         
         // See what kind of points this reaction gives.
         $ReactionType = $ReactionTypes[$Row['TagID']];
         if ($ReactionPoints = GetValue('Points', $ReactionType)) {
            if ($Row['Total'] < 1) {
               $Points += $ReactionPoints;
            } else {
               $Points += -$ReactionPoints;
            }
         }
      }
      
      // Recalculate the counts for the record.
      $TotalTags = $this->SQL->GetWhere('UserTag', array('RecordType' => $Data['RecordType'].'-Total', 'RecordID' => $Data['RecordID']))->ResultArray();
      $TotalTags = Gdn_DataSet::Index($TotalTags, array('TagID'));
      $React = array();
      $Diffs = array();
      $Set = array();
      foreach ($ReactionTypes as $TagID => $Type) {
         if (isset($TotalTags[$TagID])) {
            $React[$Type['UrlCode']] = $TotalTags[$TagID]['Total'];
            
            if ($Column = GetValue('IncrementColumn', $Type)) {
               // This reaction type also increments a column so do that too.
               TouchValue($Column, $Set, 0);
               $Set[$Column] += $TotalTags[$TagID]['Total'] * GetValue('IncrementValue', $Type, 1);
            }
         }
         
         if (GetValueR("$AttrColumn.React.{$Type['UrlCode']}", $Record) != GetValue($Type['UrlCode'], $React)) {
            $Diffs[] = $Type['UrlCode'];
         }
      }
      
      // Send back the current scores.
      foreach ($Set as $Column => $Value) {
         Gdn::Controller()->JsonTarget(
               "#{$RecordType}_{$Data['RecordID']} .Column-".$Column, 
               FormatScore($Value),
               'Html');
               
         $Record[$Column] = $Value;
      }
      // Send back the css class.
      list($AddCss, $RemoveCss) = ScoreCssClass($Record, TRUE);
      if ($RemoveCss)
         Gdn::Controller()->JsonTarget("#{$RecordType}_{$Data['RecordID']}", $RemoveCss, 'RemoveClass');
      if ($AddCss)
         Gdn::Controller()->JsonTarget("#{$RecordType}_{$Data['RecordID']}", $AddCss, 'AddClass');
         
      // Kludge, add the promoted tag to promote content.
      if ($AddCss == 'Promoted') {
         $PromotedTagID = $this->DefineTag($AddCss, 'BestOf');
         $this->SQL
            ->Options('Ignore', TRUE)
            ->Insert('UserTag', array(
                'RecordType' => $RecordType,
                'RecordID' => $Data['RecordID'],
                'UserID' => self::USERID_OTHER,
                'TagID' => $PromotedTagID,
                'DateInserted' => Gdn_Format::ToDateTime()));
         
         
//         $Sql = "insert GDN_UserTag set 
//            RecordType = :RecordType, 
//            RecordID = :RecordID, 
//            UserID = :UserID, 
//            DateInserted = :DateInserted
//            ";
      }
      
      $Record[$AttrColumn]['React'] = $React;
      $Set[$AttrColumn] = serialize($Record[$AttrColumn]);
      
      $Model->SetField($Data['RecordID'], $Set);

      // Generate the new button for the reaction.
      Gdn::Controller()->SetData('Diffs', $Diffs);
      if (function_exists('ReactionButton')) {
         $Diffs[] = 'Flag'; // always send back flag button.
         foreach ($Diffs as $UrlCode) {
            $Button = ReactionButton($Record, $UrlCode);
            Gdn::Controller()->JsonTarget(
               "#{$RecordType}_{$Data['RecordID']} .ReactButton-".$UrlCode, 
               $Button,
               'ReplaceWith');
         }
      }
      
      // Give points for the reaction.
      if ($Points <> 0 && class_exists('UserBadgeModel')) {
         UserBadgeModel::GivePoints($Record['InsertUserID'], $Points, 'Reactions');
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
      $AttrColumn = $RecordType == 'Activity' ? 'Data' : 'Attributes';
      
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
      $this->ToggleUserTag($Data, $Row, $Model);
      
      $Message = array(T(GetValue('InformMessage', $ReactionType, '')), 'Dismissable AutoDismiss');
      
      // Now deciede whether we need to log or delete the record.
      $Score = GetValueR($AttrColumn.'.React.'.$ReactionType['UrlCode'], $Row);
      $LogThreshold = GetValue('LogThreshold', $ReactionType, 10000000);
      $RemoveThreshold = GetValue('RemoveThreshold', $ReactionType, 10000000);
      
      if (!GetValueR($AttrColumn.'.RestoreUserID', $Row) || Debug()) {
         // We are only going to remove stuff if the record has not been verified.
         $Log = GetValue('Log', $ReactionType, 'Moderation');
         
         // Do a sanity check to not delete too many comments.
         $NoDelete = FALSE;
         if ($RecordType == 'Discussion' && $Row['CountComments'] > 3)
            $NoDelete = TRUE;
         
         $LogOptions = array('GroupBy' => array('RecordID'));
         $UndoButton = '';
         
         if ($Score >= min($LogThreshold, $RemoveThreshold)) {
            // Get all of the userIDs that flagged this.
            $OtherUserData = $this->SQL->GetWhere('UserTag', array('RecordType' => $RecordType, 'RecordID' => $ID, 'TagID' => $ReactionType['TagID']))->ResultArray();
            $OtherUserIDs = array();
            foreach ($OtherUserData as $UserRow) {
               if ($UserRow['UserID'] == $UserID || !$UserRow['UserID'])
                  continue;
               $OtherUserIDs[] = $UserRow['UserID'];
            }
            $LogOptions['OtherUserIDs'] = $OtherUserIDs;
         }
         
         if (!$NoDelete && $Score >= $RemoveThreshold) {
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
            $RestoreUser = Gdn::UserModel()->GetID(GetValueR($AttrColumn.'.RestoreUserID', $Row));
            $DateRestored = GetValueR($AttrColumn.'.DateRestored', $Row);
            
            // The post would have been logged, but since it has been restored we won't do that again.
            $Message = array(
            sprintf(T('The %s was already approved by %s on %s.'), T($RecordType), UserAnchor($RestoreUser), Gdn_Format::DateFull($DateRestored)),
               array('CssClass' => 'Dismissable', 'id' => 'mod')
            );
         }
      }
      
      // Check to see if we need to give the user a badge.
      $this->CheckBadges($Row['InsertUserID'], $ReactionType);
      
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
   
   public function CheckBadges($UserID, $ReactionType) {
      if (!class_exists('BadgeModel'))
         return;
      
      // Get the score on the user.
      $CountRow = $this->SQL->GetWhere('UserTag', array(
          'RecordType' => 'User',
          'RecordID' => $UserID,
          'UserID' => self::USERID_OTHER,
          'TagID' => $ReactionType['TagID']
      ))->FirstRow(DATASET_TYPE_ARRAY);
      
      $Score = $CountRow['Total'];
      
      $BadgeModel = new BadgeModel();
      $UserBadgeModel = new UserBadgeModel();
      
      $Badges = $BadgeModel->GetWhere(array('Type' => 'Reaction', 'Class' => $ReactionType['UrlCode']), 'Threshold', 'desc')->ResultArray();
      foreach ($Badges as $Badge) {
         if ($Score > $Badge['Threshold']) {
            $UserBadgeModel->Give($UserID, $Badge);
         }
      }
   }
   
   public static function ReactionTypes($UrlCode = NULL) {
      if (self::$ReactionTypes === NULL) {
         // Check the cache first.
         $ReactionTypes = Gdn::Cache()->Get('ReactionTypes');
         
         if ($ReactionTypes === Gdn_Cache::CACHEOP_FAILURE) {
            $ReactionTypes = Gdn::SQL()->Get('ReactionType', 'Sort, Name')->ResultArray();
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
            Gdn::Cache()->Store('ReactionTypes', self::$ReactionTypes);
         } else {
            self::$ReactionTypes = $ReactionTypes;
         }
      }
      
      if ($UrlCode) {
         return GetValue(strtolower($UrlCode), self::$ReactionTypes, NULL);
      }
      
      return self::$ReactionTypes;
   }
   
   public function RecalculateTotals() {
      // Calculate all of the record totals.
      $this->SQL
         ->WhereIn('RecordType', array('Discussion-Total', 'Comment-Total'))
         ->Delete('UserTag');
      
      $RecordTypes = array('Discussion', 'Comment');
      foreach ($RecordTypes as $RecordType) {
         $Sql = "insert ignore GDN_UserTag (
            RecordType,
            RecordID,
            TagID,
            UserID,
            DateInserted,
            Total
         )
         select
            '{$RecordType}-Total',
            ut.RecordID,
            ut.TagID,
            t.InsertUserID,
            min(ut.DateInserted),
            sum(ut.Total) as SumTotal
         from GDN_UserTag ut
         join GDN_{$RecordType} t
            on ut.RecordType = '{$RecordType}' and ut.RecordID = {$RecordType}ID
         group by
            RecordType,
            RecordID,
            TagID,
            t.InsertUserID";
         $this->SQL->Query($Sql);
      }      
      
      // Calculate the user totals.
      $this->SQL->Delete('UserTag', array('UserID' => self::USERID_OTHER));
      
      $Sql = "insert ignore GDN_UserTag (
         RecordType,
         RecordID,
         TagID,
         UserID,
         DateInserted,
         Total
      )
      select
         'User',
         ut.UserID,
         ut.TagID,
         -1,
         min(ut.DateInserted),
         sum(ut.Total) as SumTotal
      from GDN_UserTag ut
      where ut.RecordType in ('Discussion-Total', 'Comment-Total')
      group by
         ut.UserID,
         ut.TagID";
      $this->SQL->Query($Sql);
      
      // Now we need to update the caches on the individual discussion/comment rows.
      $TotalData = $this->SQL->GetWhere('UserTag', 
         array('RecordType' => array('Discussion-Total', 'Comment-Total')),
         'RecordType, RecordID')->ResultArray();
      
      $React = array();
      $RecordType = NULL;
      $RecordID = NULL;
      
      $ReactionTagIDs = self::ReactionTypes();
      $ReactionTagIDs = Gdn_DataSet::Index($ReactionTagIDs, array('TagID'));
      
      foreach ($TotalData as $Row) {
         $StrippedRecordType = GetValue(0, explode('-', $Row['RecordType'], 2));
         $NewRecord = $StrippedRecordType != $RecordType || $Row['RecordID'] != $RecordID;
         
         if ($NewRecord) {
            if ($RecordID)
               $this->_SaveRecordReact($RecordType, $RecordID, $React);
            
            $RecordType = $StrippedRecordType;
            $RecordID = $Row['RecordID'];
            $React = array();
         }
         $React[$ReactionTagIDs[$Row['TagID']]['UrlCode']] = $Row['Total'];
      }
      
      if ($RecordID)
         $this->_SaveRecordReact($RecordType, $RecordID, $React);
   }
   
   protected function _SaveRecordReact($RecordType, $RecordID, $React) {
      $Set = array();
      $AttrColumn = $RecordType == 'Activity' ? 'Data' : 'Attributes';
      
      $Row = $this->SQL->GetWhere($RecordType, array($RecordType.'ID' => $RecordID))->FirstRow(DATASET_TYPE_ARRAY);
      $Attributes = @unserialize($Row[$AttrColumn]);
      if (!is_array($Attributes))
         $Attributes = array();
      
      if (empty($React))
         unset($Attributes['React']);
      else
         $Attributes['React'] = $React;
      
      if (empty($Attributes))
         $Attributes = NULL;
      else
         $Attributes = serialize($Attributes);
      $Set[$AttrColumn] = $Attributes;   
      
      // Calculate the record's score too.
      foreach (self::ReactionTypes() as $Type) {
         if (($Column = GetValue('IncrementColumn', $Type)) && isset($React[$Type['UrlCode']])) {
            // This reaction type also increments a column so do that too.
            TouchValue($Column, $Set, 0);
            $Set[$Column] += $React[$Type['UrlCode']] * GetValue('IncrementValue', $Type, 1);
         }
      }
      
      // Check to see if the record is changing.
      foreach ($Set as $Key => $Value) {
         if ($Row[$Key] == $Value)
            unset($Set[$Key]);
      }
      
      if (!empty($Set)) {
         $this->SQL->Put($RecordType, $Set, array($RecordType.'ID' => $RecordID));
      }
   }
   
   public function InsertOrUpdate($Table, $Set, $Key = NULL, $DontUpdate = array(), $Op = '=') {
      if ($Key == NULL) {
         $Key = $Table.'ID';
      } elseif (is_numeric($Key)) {
         $Key = array_slice(array_keys($Set), 0, $Key);
      }
      
      $Key = array_combine($Key, $Key);
      $DontUpdate = array_fill_keys($DontUpdate, FALSE);
      
      // Make an array of the values.
      $Values = array_diff_key($Set, $Key, $DontUpdate);
      
      $Px = $this->SQL->Database->DatabasePrefix;
      $Sql = "insert {$Px}$Table 
         (".implode(', ', array_keys($Set)).')
         values (:'.implode(', :', array_keys($Set)).')
         on duplicate key update ';
      
      $Update = '';
      foreach ($Values as $Key => $Value) {
         if ($Update)
            $Update .= ', ';
         if ($Op == '=')
            $Update .= "$Key = :{$Key}_Up";
         else
            $Update .= "$Key = $Key $Op :{$Key}_Up";
      }
      $Sql .= $Update;
      
      // Construct the arguments list.
      $Args = array();
      foreach ($Set as $Key => $Value) {
         $Args[':'.$Key] = $Value;
      }
      foreach ($Values as $Key => $Value) {
         $Args[':'.$Key.'_Up'] = $Value;
      }
      

      
      // Do the final query.
      try {
         $this->SQL->Database->Query($Sql, $Args);
      } catch (Exception $Ex) {
//         decho($Sql);
//         decho($Args);
         die();
      }
   }
}