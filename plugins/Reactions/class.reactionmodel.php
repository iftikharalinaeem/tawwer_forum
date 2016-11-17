<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Class ReactionModel
 */
class ReactionModel extends Gdn_Model {

    const USERID_SUM = 0;

    const USERID_OTHER = -1;

    const FORCE_ADD = 'add';

    const FORCE_REMOVE = 'remove';

    /** @var null  */
    public static $ReactionTypes = null;

    /** @var null  */
    public static $TagIDs = null;

    /**  @var int Contains the last count from {@link GetRecordsWhere()}. */
    public $LastCount;

    /** @var Gdn_SQL */
    public $SQL;

    protected static $columns = ['UrlCode', 'Name', 'Description', 'Sort', 'Class', 'TagID', 'Active', 'Custom', 'Hidden'];

    /**
     * ReactionModel constructor.
     */
    public function __construct() {
        parent::__construct('ReactionType');
        $this->filterFields = array_merge(
            $this->filterFields,
            ['Save' => 1]
        );
        $this->PrimaryKey = 'UrlCode';
    }

    /**
     *
     *
     * @param $Name
     * @param $Type
     * @param bool $OldName
     * @return bool|Gdn_DataSet|object|string
     */
    public function defineTag($Name, $Type, $OldName = false) {
        $Row = Gdn::sql()->getWhere('Tag', array('Name' => $Name))->firstRow(DATASET_TYPE_ARRAY);

        if (!$Row && $OldName) {
            $Row = Gdn::sql()->getWhere('Tag', array('Name' => $OldName))->firstRow(DATASET_TYPE_ARRAY);
        }

        if (!$Row) {
            $TagID = Gdn::sql()->insert('Tag',
                [
                    'Name' => $Name,
                    'FullName' => $Name,
                    'Type' => 'Reaction',
                    'InsertUserID' => Gdn::session()->UserID,
                    'DateInserted' => Gdn_Format::toDateTime()
                ]
            );
        } else {
            $TagID = $Row['TagID'];
            if ($Row['Type'] != $Type || $Row['Name'] != $Name) {
                Gdn::sql()->put('Tag',
                    ['Name' => $Name, 'Type' => $Type],
                    ['TagID' => $TagID]
                );
            }
        }
        return $TagID;
    }

    /**
     *
     *
     * @param $Data
     * @param bool $OldCode
     */
    public function defineReactionType($Data, $OldCode = false) {
        $UrlCode = $Data['UrlCode'];

        // Grab the tag.
        $TagID = $this->defineTag($Data['UrlCode'], 'Reaction', $OldCode);
        $Data['TagID'] = $TagID;

        $Row = [];
        foreach (self::$columns as $Column) {
            if (isset($Data[$Column])) {
                $Row[$Column] = $Data[$Column];
                unset($Data[$Column]);
            }
        }

        // Check to see if the reaction type has been customized.
        if (!isset($Row['Custom'])) {

            // Get the cached result
            $Current = self::reactionTypes($UrlCode);
            if ($Current && val('Custom', $Current)) {
                return;
            }

            // Get the result from the DB
            $CurrentCustom = $this->SQL->getWhere('ReactionType', ['UrlCode' => $UrlCode])->value('Custom');
            if ($CurrentCustom) {
                return;
            }
        }

        if (!empty($Data)) {
            $Row['Attributes'] = dbencode($Data);
        }

        Gdn::sql()->replace('ReactionType', $Row, ['UrlCode' => $UrlCode], true);
        Gdn::cache()->remove('ReactionTypes');

        return $Data;
    }

    /**
     *
     *
     * @param array $Where
     * @return array
     */
    public static function getReactionTypes($Where = []) {
        $Types = self::reactionTypes();
        $Result = [];
        foreach ($Types as $Index => $Type) {
            if (self::filter($Type, $Where)) {
                // Set Attributes as fields
                $Attributes = val('Attributes', $Type);
                if (is_string($Attributes)) {
                    $Attributes = dbdecode($Attributes);
                    $Attributes = (is_array($Attributes)) ? $Attributes : [];
                    SetValue('Attributes', $Type, $Attributes);
                }
                // Add the result
                $Result[$Index] = $Type;
            }
        }
        return $Result;
    }

    /**
     *
     *
     * @param $Row
     * @param $Where
     * @return bool
     */
    public static function filter($Row, $Where) {
        foreach ($Where as $Column => $Value) {
            if (!isset($Row[$Column]) && $Value) {
                return false;
            }

            $RowValue = $Row[$Column];
            if (is_array($Value)) {
                if (!in_array($RowValue, $Value)) {
                    return false;
                }
            } else {
                if ($RowValue != $Value) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     *
     *
     * @param $TagID
     * @return mixed|null
     */
    public static function fromTagID($TagID) {
        if (self::$TagIDs === null) {
            $Types = self::reactionTypes();
            self::$TagIDs = Gdn_DataSet::index($Types, array('TagID'));

        }
        return val($TagID, self::$TagIDs);
    }

    /**
     *
     *
     * @param $Where
     * @param string $OrderFields
     * @param string $OrderDirection
     * @param int $Limit
     * @param int $Offset
     * @return array
     * @throws Exception
     */
    public function getRecordsWhere($Where, $OrderFields = '', $OrderDirection = '', $Limit = 30, $Offset = 0) {
        // Grab the user tags.
        $UserTags = $this->SQL
            ->limit($Limit, $Offset)
            ->getWhere('UserTag', $Where, $OrderFields, $OrderDirection)
            ->resultArray();

        $this->LastCount = count($UserTags);
        self::joinRecords($UserTags);

        return $UserTags;
    }

    /**
     *
     *
     * @param $Type
     * @param $ID
     * @param null $Operation
     * @return array
     * @throws Exception
     */
    public function getRow($Type, $ID, $Operation = null) {
        $AttrColumn = 'Attributes';

        switch ($Type) {
            case 'Comment':
                $Model = new CommentModel();
                $Row = $Model->getID($ID, DATASET_TYPE_ARRAY);
                break;
            case 'Discussion':
                $Model = new DiscussionModel();
                $Row = $Model->getID($ID);
                break;
            case 'Activity':
                $Model = new ActivityModel();
                $Row = $Model->getID($ID, DATASET_TYPE_ARRAY);
                $AttrColumn = 'Data';
                break;
            default:
                throw NotFoundException(ucfirst($Type));
        }

        $Log = null;
        if (!$Row && $Operation) {
            // The row may have been logged so try and grab it.
            $LogModel = new LogModel();
            $Log = $LogModel->getWhere(['RecordType' => $Type, 'RecordID' => $ID, 'Operation' => $Operation]);

            if (count($Log) == 0) {
                throw NotFoundException($Type);
            }
            $Log = $Log[0];
            $Row = $Log['Data'];
        }
        $Row = (array)$Row;

        // Make sure the attributes are in the row and unserialized.
        $Attributes = GetValue($AttrColumn, $Row, []);
        if (is_string($Attributes)) {
            $Attributes = dbdecode($Attributes);
        }
        if (!is_array($Attributes)) {
            $Attributes = [];
        }

        $Row[$AttrColumn] = $Attributes;
        return [$Row, $Model, $Log];
    }

    /**
     *
     *
     * @param $RecordType
     * @param $RecordID
     * @param $Reaction
     * @param int $Offset
     * @param int $Limit
     * @return array
     */
    public function getUsers($RecordType, $RecordID, $Reaction, $Offset = 0, $Limit = 10) {
        $ReactionType = self::reactionTypes($Reaction);
        if (!$ReactionType) {
            return [];
        }

        $TagID = val('TagID', $ReactionType);
        $UserTags = $this->SQL
            ->getWhere(
                'UserTag',
                ['RecordType' => $RecordType, 'RecordID' => $RecordID, 'TagID' => $TagID],
                'DateInserted',
                'desc',
                $Limit,
                $Offset
            )
            ->resultArray();

        Gdn::userModel()->joinUsers($UserTags, ['UserID']);

        return $UserTags;
    }

    /**
     *
     *
     * @param $Data
     * @param bool $RecordType
     */
    public function joinUserTags(&$Data, $RecordType = false) {
        if (!$Data) {
            return;
        }

        $IDs = [];
        $UserIDs = [];

        if (is_a($Data, 'stdClass') || (is_array($Data) && !isset($Data[0]))) {
            $Data2 = [&$Data];
        } else {
            $Data2 =& $Data;
        }

        foreach ($Data2 as $Row) {
            if (!$RecordType)
                $RT = GetValue('RecordType', $Row);
            else
                $RT = $RecordType;

            $ID = GetValue($RT.'ID', $Row);

            if ($ID)
                $IDs[$RT][$ID] = 1;
        }

        $TagsData = [];
        foreach ($IDs as $RT => $In) {
            $TagsData[$RT] = $this->SQL
                ->select('RecordID')
                ->select('UserID')
                ->select('TagID')
                ->select('DateInserted')
                ->from('UserTag')
                ->where('RecordType', $RT)
                ->whereIn('RecordID', array_keys($In))
                ->orderBy('DateInserted')
                ->get()->resultArray();
        }

        $Tags = array();
        foreach ($TagsData as $RT => $Rows) {
            foreach ($Rows as $Row) {
                $UserIDs[$Row['UserID']] = 1;
                $Tags[$RT.'-'.$Row['RecordID']][] = $Row;
            }
        }

        // Join the tags.
        foreach ($Data2 as &$Row) {
            if ($RecordType) {
                $RT = $RecordType;
            } else {
                $RT = val('RecordType', $Row);
            }
            if (!$RT) {
                $RT = 'RecordType';
            }
            $PK = $RT.'ID';
            $ID = val($PK, $Row);

            if ($ID) {
                $TagRow = val($RT.'-'.$ID, $Tags, []);
            } else {
                $TagRow = [];
            }

            setValue('UserTags', $Row, $TagRow);
        }
    }

    /**
     * Merge user reactions for all of the users that were never merged.
     *
     * @return int
     */
    public function mergeOldUserReactions() {
        $merges = $this->SQL->getWhere('UserMerge', ['ReactionsMerged' => 0], 'DateInserted')->resultArray();

        $count = 0;
        foreach ($merges as $merge) {
            $this->mergeUsers($merge['OldUserID'], $merge['NewUserID']);
            $this->SQL->put('UserMerge', ['ReactionsMerged' => 1], ['MergeID' => $merge['MergeID']]);
            $count++;
        }
        return $count;
    }

    /**
     * Merge the reactions of two users.
     *
     * This copies the reactions from the {@link $oldUserID} to the {@link $newUserID}
     *
     * @param int $oldUserID The ID of the old user.
     * @param int $newUserID The ID of the new user.
     * @return array
     */
    public function mergeUsers($oldUserID, $newUserID) {
        $sql = $this->SQL;

        // Get all of the reactions the user has made.
        $reactions = $sql->getWhere('UserTag',
                ['UserID' => $oldUserID, 'RecordType' => ['Discussion', 'Comment', 'Activity', 'ActivityComment']]
            )->resultArray();

        // Go through the reactions and move them from the old user to the new user.
        foreach ($reactions as $reaction) {
            list($row, $model, $_) = $this->getRow($reaction['RecordType'], $reaction['RecordID']);

            // Add the reaction for the new user.
            if ($reaction['Total'] > 0) {
                $newReaction = [
                    'RecordType' => $reaction['RecordType'],
                    'RecordID' => $reaction['RecordID'],
                    'TagID' => $reaction['TagID'],
                    'UserID' => $newUserID,
                    'DateInserted' => $reaction['DateInserted']
                ];
                $this->toggleUserTag($newReaction, $row, $model, self::FORCE_ADD);
            }

            // Remove the reaction for the old user.
            $this->toggleUserTag($reaction, $row, $model, self::FORCE_REMOVE);
        }

        return $reactions;
    }

    /**
     *
     *
     * @param $Data
     */
    public static function joinRecords(&$Data) {
        $IDs = [];
        $AllowedCats = DiscussionModel::categoryPermissions();

        if ($AllowedCats === false) {
            // This user does not have permission to view anything.
            $Data = [];
            return;
        }

        // Gather all of the ids to fetch.
        foreach ($Data as &$Row) {
            $RecordType = stringEndsWith($Row['RecordType'], '-Total', true, true);
            $Row['RecordType'] = $RecordType;
            $ID = $Row['RecordID'];
            $IDs[$RecordType][$ID] = $ID;
        }

        // Fetch all of the data in turn.
        $JoinData = array();
        foreach ($IDs as $RecordType => $RecordIDs) {
            if ($RecordType == 'Comment') {
                Gdn::sql()
                    ->select('d.Name, d.CategoryID')
                    ->join('Discussion d', 'd.DiscussionID = r.DiscussionID');
            }

            $Rows = Gdn::sql()
                ->select('r.*')
                ->whereIn($RecordType.'ID', array_values($RecordIDs))
                ->get($RecordType.' r')->resultArray();

            $JoinData[$RecordType] = Gdn_DataSet::index($Rows, array($RecordType.'ID'));
        }

        // Join the rows.
        $Unset = [];
        foreach ($Data as $Index => &$Row) {
            $RecordType = $Row['RecordType'];
            $ID = $Row['RecordID'];


            if (!isset($JoinData[$RecordType][$ID])) {
                $Unset[] = $Index;
                continue; // orphaned?
            }

            $Record = $JoinData[$RecordType][$ID];

            if ($AllowedCats !== true) {
                // Check to see if the user has permission to view this record.
                $CategoryID = val('CategoryID', $Record, -1);
                if (!in_array($CategoryID, $AllowedCats)) {
                    $Unset[] = $Index;
                    continue;
                }
            }

            $Row = array_merge($Row, $Record);

            switch ($RecordType) {
                case 'Discussion':
                    $Url = discussionUrl($Row, '', '#latest');
                    break;
                case 'Comment':
                    $Row['Name'] = sprintf(t('Re: %s'), $Row['Name']);
                    $Url = commentUrl($Row, '/');
                    break;
                default:
                    $Url = '';
            }
            $Row['Url'] = $Url;

            // Join the category
            $Category = CategoryModel::categories(val('CategoryID', $Row, ''));
            $Row['CategoryCssClass'] = val('CssClass', $Category);
        }

        foreach ($Unset as $Index) {
            unset($Data[$Index]);
        }

        // Join the users.
        Gdn::userModel()->joinUsers($Data, ['InsertUserID']);

        if (!empty($Unset)) {
            $Data = array_values($Data);
        }
    }

    /**
     * Toggle a reaction on a record.
     *
     * @param array $Data The reaction data to add. This is an array with the following keys:
     * - RecordType: The type of record (table name) being reacted to.
     * - RecordID: The primary key ID of the record being reacted to.
     * - TagID: The reaction tag to use.
     * - UserID: The user reacting.
     * - DateInserted: Optional. The date of the reaction.
     * @param array $Record The record being reacted to as obtained from {@link ReactionModel::getRow()}.
     * @param Gdn_Model $Model The model of the record being reacted to as obtained from {@link ReactionModel::getRow()}.
     * @param bool $Delete A hint to the toggle. One of the following:
     * - ReactionModel::FORCE_ADD: Add the reaction if it does not exist. Otherwise do nothing.
     * - ReactionModel::FORCE_REMOVE: Remove the reaction if it exists. Otherwise do nothing.
     * @return mixed
     */
    public function toggleUserTag(&$Data, &$Record, $Model, $Delete = null) {
        $Inc = val('Total', $Data, 1);
        touchValue('Total', $Data, $Inc);
        touchValue('DateInserted', $Data, Gdn_Format::toDateTime());
        $ReactionTypes = self::ReactionTypes();
        $ReactionTypes = Gdn_DataSet::Index($ReactionTypes, ['TagID']);

        // See if there is already a user tag.
        $Where = arrayTranslate($Data, ['RecordType', 'RecordID', 'UserID']);

        $UserTags = $this->SQL->getWhere('UserTag', $Where)->resultArray();
        $UserTags = Gdn_DataSet::index($UserTags, ['TagID']);
        $Insert = true;

        if (isset($UserTags[$Data['TagID']])) {
            // The user is toggling a tag they've already done.
            if ($Delete === self::FORCE_ADD) {
                // The use is forcing a tag add so this is a no-op.
                return;
            }
            $Insert = false;

            $Inc = -$UserTags[$Data['TagID']]['Total'];
            $Data['Total'] = $Inc;
        }

        if ($Insert && ($Delete === true || $Delete === self::FORCE_REMOVE)) {
            return;
        }

        $RecordType = $Data['RecordType'];
        $AttrColumn = $RecordType == 'Activity' ? 'Data' : 'Attributes';

        // Delete all of the tags.
        if (count($UserTags) > 0) {
            $DeleteWhere = $Where;
            $DeleteWhere['TagID'] = array_keys($UserTags);
            $this->SQL->delete('UserTag', $DeleteWhere);
        }

        if ($Insert) {
            // Insert the tag.
            $this->SQL->options('Ignore', true)->insert('UserTag', $Data);

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
            $Args = ArrayTranslate($Row, [
                'RecordType' => ':RecordType',
                'RecordID' => ':RecordID',
                'TagID' => ':TagID',
                'UserID' => ':UserID',
                'DateInserted' => ':DateInserted']
            );
            $Args[':Total'] = -$Row['Total'];
            $Args[':Total2'] = $Args[':Total'];

            // Increment the record total.
            $Args[':RecordType'] = $RecordType.'-Total';
            $Args[':UserID'] = $Record['InsertUserID'];
            $this->SQL->Database->query($Sql, $Args);

            // Increment the user total.
            $Args[':RecordType'] = 'User';
            $Args[':RecordID'] = $Record['InsertUserID'];
            $Args[':UserID'] = self::USERID_OTHER;
            $this->SQL->Database->query($Sql, $Args);

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
        $TotalTags = $this->SQL
            ->getWhere('UserTag', ['RecordType' => $Data['RecordType'].'-Total', 'RecordID' => $Data['RecordID']])
            ->resultArray();
        $TotalTags = Gdn_DataSet::index($TotalTags, ['TagID']);
        $React = [];
        $Diffs = [];
        $Set = [];

        foreach ($ReactionTypes as $TagID => $Type) {
            if (isset($TotalTags[$TagID])) {
                $React[$Type['UrlCode']] = $TotalTags[$TagID]['Total'];

                if ($Column = val('IncrementColumn', $Type)) {
                    // This reaction type also increments a column so do that too.
                    touchValue($Column, $Set, 0);
                    $Set[$Column] += $TotalTags[$TagID]['Total'] * val('IncrementValue', $Type, 1);
                }
            }

            if (valr("$AttrColumn.React.{$Type['UrlCode']}", $Record) != val($Type['UrlCode'], $React)) {
                $Diffs[] = $Type['UrlCode'];
            }
        }

        Gdn::controller()->EventArguments['ReactionTypes'] &= $ReactionTypes;
        Gdn::controller()->EventArguments['Record'] = $Record;
        Gdn::controller()->EventArguments['Set'] = &$Set;
        Gdn::controller()->fireEvent('BeforeReactionsScore');

        // Send back the current scores.
        foreach ($Set as $Column => $Value) {
            Gdn::controller()->jsonTarget("#{$RecordType}_{$Data['RecordID']} .Column-".$Column, self::formatScore($Value), 'Html');
            $Record[$Column] = $Value;
        }
        // Send back the css class.
        list($AddCss, $RemoveCss) = self::scoreCssClass($Record, TRUE);
        if ($RemoveCss) {
            Gdn::controller()->jsonTarget("#{$RecordType}_{$Data['RecordID']}", $RemoveCss, 'RemoveClass');
        }
        if ($AddCss) {
            Gdn::controller()->jsonTarget("#{$RecordType}_{$Data['RecordID']}", $AddCss, 'AddClass');
        }

        // Send back a delete for the user reaction.
        if (!$Insert) {
            Gdn::controller()->jsonTarget("#{$RecordType}_{$Data['RecordID']} .UserReactionWrap[data-userid={$Data['UserID']}]", '', 'Remove');
        }

        // Kludge, add the promoted tag to promote content.
        if ($AddCss == 'Promoted') {
            $PromotedTagID = $this->defineTag($AddCss, 'BestOf');
            $this->SQL
                ->options('Ignore', true)
                ->insert('UserTag', [
                    'RecordType' => $RecordType,
                    'RecordID' => $Data['RecordID'],
                    'UserID' => self::USERID_OTHER,
                    'TagID' => $PromotedTagID,
                    'DateInserted' => Gdn_Format::toDateTime()
                ]);
        }

        $Record[$AttrColumn]['React'] = $React;
        $Set[$AttrColumn] = dbencode($Record[$AttrColumn]);

        $Model->setField($Data['RecordID'], $Set);

        // Generate the new button for the reaction.
        Gdn::controller()->setData('Diffs', $Diffs);
        if (function_exists('ReactionButton')) {
            $Diffs[] = 'Flag'; // always send back flag button.
            foreach ($Diffs as $UrlCode) {
                $Button = reactionButton($Record, $UrlCode, ['LinkClass' => 'FlyoutButton']);
                $reactionsPlugin = ReactionsPlugin::instance();
                $reactionsPlugin->EventArguments['UrlCode'] = $UrlCode;
                $reactionsPlugin->EventArguments['Record'] = $Record;
                $reactionsPlugin->EventArguments['Insert'] = $Insert;
                $reactionsPlugin->EventArguments['TagID'] = val('TagID', $Data);
                $reactionsPlugin->EventArguments['Button'] = &$Button;
                $reactionsPlugin->fireEvent('ReactionsButtonReplacement');
                Gdn::controller()->jsonTarget("#{$RecordType}_{$Data['RecordID']} .ReactButton-".$UrlCode, $Button, 'ReplaceWith');
            }
        }

        // Give points for the reaction.
        if ($Points <> 0) {
            if (method_exists('CategoryModel', 'GivePoints')) {
                $CategoryID = 0;
                if (isset($Record['CategoryID'])) {
                    $CategoryID = $Record['CategoryID'];
                } elseif (isset($Record['DiscussionID'])) {
                    $CategoryID = $this->SQL
                        ->getWhere('Discussion', array('DiscussionID' => $Record['DiscussionID']))
                        ->value('CategoryID');
                }

                CategoryModel::givePoints($Record['InsertUserID'], $Points, 'Reactions', $CategoryID);
            } else {
                UserModel::givePoints($Record['InsertUserID'], $Points, 'Reactions');
            }
        }

        return $Insert;
    }

    /**
     *
     *
     * @param string $RecordType
     * @param int $ID
     * @param string $ReactionUrlCode
     * @param bool $selfReact Whether a user can react to their own post
     */
    public function react($RecordType, $ID, $ReactionUrlCode, $UserID = null, $selfReact = false) {
        if (is_null($UserID)) {
            $UserID = Gdn::session()->UserID;
            $IsModerator = checkPermission('Garden.Moderation.Manage');
            $IsCurator = checkPermission('Garden.Curation.Manage');
        } else {
            $User = Gdn::userModel()->getID($UserID);
            $IsModerator = Gdn::userModel()->checkPermission($User, 'Garden.Moderation.Manage');
            $IsCurator = Gdn::userModel()->checkPermission($User, 'Garden.Curation.Manage');
        }

        $Undo = false;
        if (stringBeginsWith($ReactionUrlCode, 'Undo-', true)) {
            $Undo = true;
            $ReactionUrlCode = stringBeginsWith($ReactionUrlCode, 'Undo-', true, true);
        }
        $RecordType = ucfirst($RecordType);
        $ReactionUrlCode = strtolower($ReactionUrlCode);
        $ReactionType = self::reactionTypes($ReactionUrlCode);
        $AttrColumn = $RecordType == 'Activity' ? 'Data' : 'Attributes';

        if (!$ReactionType) {
            throw NotFoundException($ReactionUrlCode);
        }

        $LogOperation = val('Log', $ReactionType);

        list($Row, $Model, $Log) = $this->getRow($RecordType, $ID, $LogOperation);

        if (!$selfReact && !$IsModerator && ($Row['InsertUserID'] == $UserID)) {
            throw new Gdn_UserException(T("You can't react to your own post."));
        }

        // Check and see if moderators are protected.
        if (val('Protected', $ReactionType)) {
            $InsertUser = Gdn::userModel()->getID($Row['InsertUserID']);
            if (Gdn::userModel()->checkPermission($InsertUser, 'Garden.Moderation.Manage')) {
                throw new Gdn_UserException(t("You can't flag a moderator's post."));
            }
        }

        // Figure out the increment.
        if ($IsCurator) {
            $Inc = val('ModeratorInc', $ReactionType, 1);
        } else {
            $Inc = 1;
        }

        // Save the user Tag.
        $Data = [
            'RecordType' => $RecordType,
            'RecordID' => $ID,
            'TagID' => $ReactionType['TagID'],
            'UserID' => $UserID,
            'Total' => $Inc
        ];
        $Inserted = $this->toggleUserTag($Data, $Row, $Model, $Undo);

        $Message = [t(val('InformMessage', $ReactionType, '')), 'Dismissable AutoDismiss'];

        // Now decide whether we need to log or delete the record.
        $Score = valr($AttrColumn.'.React.'.$ReactionType['UrlCode'], $Row);
        $LogThreshold = val('LogThreshold', $ReactionType, 10000000);
        $RemoveThreshold = val('RemoveThreshold', $ReactionType, 10000000);

        if (!valr($AttrColumn.'.RestoreUserID', $Row) || debug()) {
            // We are only going to remove stuff if the record has not been verified.
            $Log = val('Log', $ReactionType, 'Moderation');

            // Do a sanity check to not delete too many comments.
            $NoDelete = false;
            if ($RecordType == 'Discussion' && $Row['CountComments'] > 3) {
                $NoDelete = true;
            }

            $LogOptions = ['GroupBy' => ['RecordID']];
            $UndoButton = '';

            if ($Score >= min($LogThreshold, $RemoveThreshold)) {
                // Get all of the userIDs that flagged this.
                $OtherUserData = $this->SQL
                    ->getWhere('UserTag', ['RecordType' => $RecordType, 'RecordID' => $ID, 'TagID' => $ReactionType['TagID']])
                    ->resultArray();
                $OtherUserIDs = [];
                foreach ($OtherUserData as $UserRow) {
                    if ($UserRow['UserID'] == $UserID || !$UserRow['UserID']) {
                        continue;
                    }
                    $OtherUserIDs[] = $UserRow['UserID'];
                }
                $LogOptions['OtherUserIDs'] = $OtherUserIDs;
            }

            if (!$NoDelete && $Score >= $RemoveThreshold) {
                // Remove the record to the log.
                $Model->delete($ID, ['Log' => $Log, 'LogOptions' => $LogOptions]);
                $Message = [
                    sprintf(t('The %s has been removed for moderation.'),
                    t($RecordType)).' '.$UndoButton,
                   ['CssClass' => 'Dismissable', 'id' => 'mod']
                ];
                // Send back a command to remove the row in the browser.
                if ($RecordType == 'Discussion') {
                    Gdn::controller()->jsonTarget('.ItemDiscussion', '', 'SlideUp');
                    Gdn::controller()->jsonTarget('#Content .Comments', '', 'SlideUp');
                    Gdn::controller()->jsonTarget('.CommentForm', '', 'SlideUp');
                } else {
                    Gdn::controller()->jsonTarget("#{$RecordType}_$ID", '', 'SlideUp');
                }
            } elseif ($Score >= $LogThreshold) {
                LogModel::insert($Log, $RecordType, $Row, $LogOptions);
                $Message = [
                    sprintf(t('The %s has been flagged for moderation.'),
                    t($RecordType)).' '.$UndoButton,
                    ['CssClass' => 'Dismissable', 'id' => 'mod']
                ];
            }
        } else {
            if ($Score >= min($LogThreshold, $RemoveThreshold)) {
                $RestoreUser = Gdn::userModel()->getID(GetValueR($AttrColumn.'.RestoreUserID', $Row));
                $DateRestored = GetValueR($AttrColumn.'.DateRestored', $Row);

                // The post would have been logged, but since it has been restored we won't do that again.
                $Message = [
                    sprintf(t('The %s was already approved by %s on %s.'), t($RecordType), userAnchor($RestoreUser), Gdn_Format::dateFull($DateRestored)),
                    ['CssClass' => 'Dismissable', 'id' => 'mod']
                ];
            }
        }

        // Check to see if we need to give the user a badge.
        $this->checkBadges($Row['InsertUserID'], $ReactionType);

        if ($Message) {
            Gdn::controller()->informMessage($Message[0], $Message[1]);
        }

        ReactionsPlugin::instance()->EventArguments = array(
            'RecordType' => $RecordType,
            'RecordID' => $ID,
            'Record' => $Row,
            'ReactionUrlCode' => $ReactionUrlCode,
            'ReactionData' => $Data,
            'Insert' => $Inserted,
            'UserID' => $UserID
        );
        ReactionsPlugin::instance()->fireEvent('Reaction');
    }

    /**
     *
     *
     * @param $UserID
     * @param $ReactionType
     */
    public function checkBadges($UserID, $ReactionType) {
        if (!class_exists('BadgeModel')) {
            return;
        }

        // Get the score on the user.
        $CountRow = $this->SQL->getWhere('UserTag', [
            'RecordType' => 'User',
            'RecordID' => $UserID,
            'UserID' => self::USERID_OTHER,
            'TagID' => $ReactionType['TagID']
        ])->firstRow(DATASET_TYPE_ARRAY);

        $Score = $CountRow['Total'];

        $BadgeModel = new BadgeModel();
        $UserBadgeModel = new UserBadgeModel();

        $Badges = $BadgeModel
            ->getWhere(array('Type' => 'Reaction', 'Class' => $ReactionType['UrlCode']), 'Threshold', 'desc')
            ->resultArray();
        foreach ($Badges as $Badge) {
            if ($Score >= $Badge['Threshold']) {
                $UserBadgeModel->give($UserID, $Badge);
            }
        }
    }

    /**
     *
     *
     * @param null $UrlCode
     * @return mixed|null
     */
    public static function reactionTypes($UrlCode = null) {
        if (self::$ReactionTypes === null) {
            // Check the cache first.
            $ReactionTypes = Gdn::cache()->get('ReactionTypes');

            if ($ReactionTypes === Gdn_Cache::CACHEOP_FAILURE) {
                $ReactionTypes = Gdn::sql()->get('ReactionType', 'Sort, Name')->resultArray();
                foreach ($ReactionTypes as $Type) {
                    $Row = $Type;
                    $Attributes = dbdecode($Row['Attributes']);
                    //unset($Row['Attributes']); // No! Wipes field when it's re-saved.
                    if (is_array($Attributes)) {
                        foreach ($Attributes as $Name => $Value) {
                            $Row[$Name] = $Value;
                        }
                    }

                    self::$ReactionTypes[strtolower($Row['UrlCode'])] = $Row;
                }
                Gdn::cache()->store('ReactionTypes', self::$ReactionTypes);
            } else {
                self::$ReactionTypes = $ReactionTypes;
            }
        }

        if ($UrlCode) {
            return val(strtolower($UrlCode), self::$ReactionTypes, NULL);
        }

        return self::$ReactionTypes;
    }

    /**
     *
     *
     * @throws Exception
     */
    public function recalculateTotals() {
        // Calculate all of the record totals.
        $this->SQL
            ->whereIn('RecordType', ['Discussion-Total', 'Comment-Total'])
            ->delete('UserTag');

        $RecordTypes = ['Discussion', 'Comment'];
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
            $this->SQL->query($Sql);
        }

        // Calculate the user totals.
        $this->SQL->delete('UserTag', ['UserID' => self::USERID_OTHER]);

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
        $this->SQL->query($Sql);

        // Now we need to update the caches on the individual discussion/comment rows.
        $this->recalculateRecordCache();
    }

    /**
     *
     *
     * @param bool $Day
     * @return int
     */
    public function recalculateRecordCache($Day = FALSE) {
        $Where = array('RecordType' => array('Discussion-Total', 'Comment-Total'));

        if ($Day) {
            $Day = Gdn_Format::toTimestamp($Day);
            $Where['DateInserted >='] = gmdate('Y-m-d', $Day);
            $Where['DateInserted <'] = gmdate('Y-m-d', strtotime('+1 day', $Day));
        }

        $TotalData = $this->SQL->getWhere('UserTag',
            $Where,
            'RecordType, RecordID')->resultArray();

        $React = [];
        $RecordType = null;
        $RecordID = null;

        $ReactionTagIDs = self::reactionTypes();
        $ReactionTagIDs = Gdn_DataSet::Index($ReactionTagIDs,['TagID']);

        $Count = 0;
        foreach ($TotalData as $Row) {
            if (!isset($ReactionTagIDs[$Row['TagID']])) {
                continue;
            }

            $Count++;
            $StrippedRecordType = GetValue(0, explode('-', $Row['RecordType'], 2));
            $NewRecord = $StrippedRecordType != $RecordType || $Row['RecordID'] != $RecordID;

            if ($NewRecord) {
                if ($RecordID) {
                    $this->_saveRecordReact($RecordType, $RecordID, $React);
                }

                $RecordType = $StrippedRecordType;
                $RecordID = $Row['RecordID'];
                $React = [];
            }
            $React[$ReactionTagIDs[$Row['TagID']]['UrlCode']] = $Row['Total'];
        }

        if ($RecordID) {
            $this->_saveRecordReact($RecordType, $RecordID, $React);
        }

        return $Count;
    }

    /**
     *
     *
     * @param $RecordType
     * @param $RecordID
     * @param $React
     */
    protected function _saveRecordReact($RecordType, $RecordID, $React) {
        $Set = array();
        $AttrColumn = $RecordType == 'Activity' ? 'Data' : 'Attributes';

        $Row = $this->SQL->getWhere($RecordType, [$RecordType.'ID' => $RecordID])->firstRow(DATASET_TYPE_ARRAY);
        $Attributes = dbdecode($Row[$AttrColumn]);
        if (!is_array($Attributes)) {
            $Attributes = [];
        }

        if (empty($React)) {
            unset($Attributes['React']);
        } else {
            $Attributes['React'] = $React;
        }

        if (empty($Attributes)) {
            $Attributes = null;
        } else {
            $Attributes = dbencode($Attributes);
        }
        $Set[$AttrColumn] = $Attributes;

        // Calculate the record's score too.
        foreach (self::reactionTypes() as $Type) {
            if (($Column = val('IncrementColumn', $Type)) && isset($React[$Type['UrlCode']])) {
                // This reaction type also increments a column so do that too.
                touchValue($Column, $Set, 0);
                $Set[$Column] += $React[$Type['UrlCode']] * val('IncrementValue', $Type, 1);
            }
        }

        // Check to see if the record is changing.
        foreach ($Set as $Key => $Value) {
            if ($Row[$Key] == $Value) {
                unset($Set[$Key]);
            }
        }

        if (!empty($Set)) {
            $this->SQL->put($RecordType, $Set, [$RecordType.'ID' => $RecordID]);
        }
    }

    /**
     *
     *
     * @param $Table
     * @param $Set
     * @param null $Key
     * @param array $DontUpdate
     * @param string $Op
     */
    public function insertOrUpdate($Table, $Set, $Key = null, $DontUpdate = [], $Op = '=') {
        if ($Key == null) {
            $Key = $Table.'ID';
        } elseif (is_numeric($Key)) {
            $Key = array_slice(array_keys($Set), 0, $Key);
        }

        $Key = array_combine($Key, $Key);
        $DontUpdate = array_fill_keys($DontUpdate, false);

        // Make an array of the values.
        $Values = array_diff_key($Set, $Key, $DontUpdate);

        $Px = $this->SQL->Database->DatabasePrefix;
        $Sql = "insert {$Px}$Table
            (".implode(', ', array_keys($Set)).')
            values (:'.implode(', :', array_keys($Set)).')
            on duplicate key update ';

        $Update = '';
        foreach ($Values as $Key => $Value) {
            if ($Update) {
                $Update .= ', ';
            }
            if ($Op == '=') {
                $Update .= "$Key = :{$Key}_Up";
            } else {
                $Update .= "$Key = $Key $Op :{$Key}_Up";
            }
        }
        $Sql .= $Update;

        // Construct the arguments list.
        $Args = [];
        foreach ($Set as $Key => $Value) {
            $Args[':'.$Key] = $Value;
        }
        foreach ($Values as $Key => $Value) {
            $Args[':'.$Key.'_Up'] = $Value;
        }

        // Do the final query.
        try {
            $this->SQL->Database->query($Sql, $Args);
        } catch (Exception $Ex) {
            die();
        }
    }

    /**
     * All the score to be formatted differently.
     *
     * @param $Score
     * @return int
     */
    public static function formatScore($Score) {
        if (function_exists('FormatScore')) {
            return formatScore($Score);
        }
        return (int)$Score;
    }

    /**
     * Give the CSS class for the current score.
     *
     * @param $Row
     * @param bool $All
     * @return array|string
     */
    public static function scoreCssClass($Row, $All = false) {
        if (function_exists('ScoreCssClass')) {
            return scoreCssClass($Row, $All);
        }

        $Score = val('Score', $Row);
        if (!$Score) {
            $Score = 0;
        }

        $Bury = c('Reactions.BuryValue', -5);
        $Promote = c('Reactions.PromoteValue', 5);

        if ($Score <= $Bury) {
            $Result = $All ? 'Un-Buried' : 'Buried';
        } elseif ($Score >= $Promote) {
            $Result = 'Promoted';
        } else {
            $Result = '';
        }

        if ($All) {
            return array($Result, 'Promoted Buried Un-Buried');
        } else {
            return $Result;
        }
    }

    /**
     * Save a reaction.
     *
     * @param array $formPostValues
     * @param bool $settings Unused
     * @return bool
     */
    public function save($formPostValues, $settings = false) {
        $primaryKeyValue = val($this->PrimaryKey, $formPostValues);
        if (!$primaryKeyValue) {
            return false;
        }

        $reaction = self::reactionTypes($primaryKeyValue);
        if ($reaction) {
            // Preserve non modified attribute data
            unset($reaction['Attributes']);
            $formPostValues = array_merge($reaction, $formPostValues);
        }

        $this->defineReactionType($formPostValues);

        // Destroy the cache.
        Gdn::cache()->remove('ReactionTypes');

        return true;
    }
}
