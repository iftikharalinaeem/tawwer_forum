<?php
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Class VanillaPopPlugin
 */
class VanillaPopPlugin extends Gdn_Plugin {
    /// Properties ///
    static $FormatDefaults = [
        'DiscussionSubject' => '[{Title}] {Name}',
        'DiscussionBody' => "{Body}\n\n-- \n{Signature}",
        'CommentSubject' => 'Re: [{Title}] {Discussion.Name}',
        'CommentBody' => "{Body}\n\n-- \n{Signature}",
        'ConfirmationBody' => "Your request has been received (ticket #{ID}).\n\nThis is just a confirmation email, but you can reply directly to follow up.\n\nYou wrote:\n{Quote}\n\n-- \n{Signature}"];

    /**
     * @var array A list of email domains for receiving email.
     */
    protected $emailDomains = [
        'vanillaforums.com' => 'vanillaforums.email',
        'vanillacommunity.com' => 'vanillacommunity.email',
        'vanillacommunities.com' => 'vanillacommunities.email'
    ];


    /// Methods ///

    public static function addIDToEmail($Email, $ID) {
        if (!C('Plugins.VanillaPop.AugmentFrom', true)) {
            return;
        }

        // Encode the message ID in the from.
        $FromParts = explode('@', $Email, 2);
        if (count($FromParts) == 2) {
            $Email = "{$FromParts[0]}+$ID@{$FromParts[1]}";
        }
        return $Email;
    }

    public static function checkUserPermission($UserID, $Permission) {
        $Permissions = Gdn::userModel()->definePermissions($UserID, false);
        $Result = in_array($Permission, $Permissions) || array_key_exists($Permission, $Permissions);
        return $Result;
    }

//   public static function formatPlainText($Body, $Format) {
//      $Result = Gdn_Format::to($Body, $Format);
//
//      if ($Format != 'Text')
//         $Result = Gdn_Format::text($Result, false);
//      $Result = trim(html_entity_decode($Result, ENT_QUOTES, 'UTF-8'));
//      return $Result;
//   }

    public static function formatEmailBody($Body, $Route = '', $Quote = '', $Options = false) {
        // Construct the signature.
        if ($Route) {
            $Signature = formatString(T('ReplyOrFollow'))."\n".externalUrl($Route);
        } elseif ($Route === false) {
            $Signature = externalUrl('/');
        } else {
            $Signature = formatString(T('ReplyOnly'));
        }

        if ($Quote) {
            if (is_array($Quote)) {
                $Quote = Gdn_Format::plainText($Quote['Body'], val('Format', $Quote, 'Text'));
            }

            $Quote = "\n\n".t('You wrote:')."\n\n".self::formatQuoteText($Quote);
        }

        $Result = formatString(T('EmailTemplate'), ['Body' => $Body, 'Signature' => $Signature, 'Quote' => $Quote]);
        return $Result;
    }

    public static function emailSignature($Route = '', $CanView = true, $CanReply = true) {
        if (!$Route) {
            $CanView = false;
        }

        if ($CanView && $CanReply) {
            $Signature = formatString(T('ReplyOrFollow'))."\n".externalUrl($Route);
        } elseif ($CanView) {
            $Signature = formatString(T('FollowOnly'))."\n".externalUrl($Route);
        } elseif ($CanReply) {
            $Signature = formatString(T('ReplyOnly'));
        } else {
            $Signature = externalUrl('/');
        }
        return $Signature;
    }

    public static function formatQuoteText($Text) {
        $Result = '> '.str_replace("\n", "\n> ", $Text);
        return $Result;
    }

    public static function labelCode($SchemaRow) {
        if (isset($SchemaRow['LabelCode'])) {
            return $SchemaRow['LabelCode'];
        }

        $LabelCode = $SchemaRow['Name'];
        if (strpos($LabelCode, '.') !== false) {
            $LabelCode = trim(strrchr($LabelCode, '.'), '.');
        }

        // Split camel case labels into seperate words.
        $LabelCode = preg_replace('`(?<![A-Z0-9])([A-Z0-9])`', ' $1', $LabelCode);
        $LabelCode = preg_replace('`([A-Z0-9])(?=[a-z])`', ' $1', $LabelCode);
        $LabelCode = trim($LabelCode);

        return $LabelCode;
    }

    public static function log($Message) {
//      $Line = Gdn_Format::toDateTime().' '.$Message."\n";
//      file_put_contents(PATH_UPLOADS.'/email/log.txt', $Line, FILE_APPEND);
    }

    public static function parseEmailAddress($Email) {
        $Name = '';
        if (preg_match('`([^<]*)<([^>]+)>`', $Email, $Matches)) {
            $Name = trim(trim($Matches[1]), '"');
            $Email = trim($Matches[2]);
        }

        if (!$Name) {
            $Name = trim(substr($Email, 0, strpos($Email, '@')), '@');

            $NameParts = explode('.', $Name);
            $NameParts = array_map('ucfirst', $NameParts);
            $Name = implode(' ', $NameParts);
        }

        $Result = [$Name, $Email];
        return $Result;
    }

    public static function parseEmailHeader($Header) {
        $Result = [];
        $Parts = explode("\n", $Header);

        $i = null;
        foreach ($Parts as $Part) {
            if (!$Part) {
                continue;
            }
            if (preg_match('`^\s`', $Part)) {
                if (isset($Result[$i])) {
                    $Result[$i] .= "\n".$Part;
                }
            } else {
                self::log("Headerline: $Part");
                list($Name, $Value) = explode(':', $Part, 2);
                $i = trim($Name);
                $Result[$i] = ltrim($Value);
            }
        }

        return $Result;
    }

    public static function parseRoute($Route) {
        if (preg_match('`/?(?:vanilla/)?discussion/(\d+)`i', $Route, $Matches)) {
            $Type = 'Discussion';
            $ID = $Matches[1];
        } elseif (preg_match('`/?(?:vanilla/)?discussion/comment/(\d+)`i', $Route, $Matches)) {
            $Type = 'Comment';
            $ID = $Matches[1];
        } elseif (preg_match('`/?(?:conversations/)?messages/\d+#(?:Message_)?(\d+)`i', $Route, $Matches)) {
            $Type = 'Message';
            $ID = $Matches[1];
        } else {
            return [null, null];
        }

        return [$Type, $ID];
    }

    public static function parseType($Email) {
        $Type = null;
        $ID = null;
        if (preg_match('`\+([a-z]+-?[0-9]+)@`', $Email, $Matches)) {
            list($Type, $ID) = self::parseUID($Matches[1]);
        } elseif (preg_match('`\+noreply@`i', $Email, $Matches)) {
            $Type = 'noreply';
            $ID = null;
        } else {
            // See if there is a category in the email address.
            $Parts = explode('@', $Email);
            $Codes = explode('.', $Parts[0]);

            if (count($Codes) > 0) {
                $Category = CategoryModel::categories($Codes[0]);
                if ($Category) {
                    $Type = 'Category';
                    $ID = $Category['CategoryID'];
                }
            }
        }
        return [$Type, $ID];
    }

    public static $Types = ['d' => 'Discussion', 'c' => 'Comment', 'u' => 'User', 'cv' => 'Conversation', 'm' => 'Message'];

    public static function parseUID($UID) {
        // Strip off email stuff.
        if (preg_match('`<([^@]+)@`', $UID, $Matches)) {
            $UID = trim(trim($Matches[1]), '"');
        }

        if (strcasecmp($UID, 'noreply') == 0) {
            return ['noreply', null];
        }

        if (preg_match('`([a-z]+)-?([0-9]+)`i', $UID, $Matches)) {
            $Type = val($Matches[1], self::$Types, null);
            if ($Type) {
                $ID = $Matches[2];
                return [$Type, $ID];
            }
        } else {
            // This might be a category.
            $Category = CategoryModel::categories($UID);
            if ($Category) {
                return ['Category', $Category['CategoryID']];
            } else {
                return [null, null];
            }
        }
    }

    protected function save($Data, $Sender) {
        $ReplyType = null;
        $ReplyID = null;

        if (val('ReplyTo', $Data)) {
            trace("ReplyTo: {$Data['ReplyTo']}");
            // See if we are replying to something specifically.
            list($ReplyType, $ReplyID) = self::parseUID($Data['ReplyTo']);
        }

        if (!$ReplyType) {
            // Grab the reply from the to.
            list($ToName, $ToEmail) = self::parseEmailAddress(val('To', $Data));
            list($ReplyType, $ReplyID) = self::parseType($ToEmail);
        }

        if ((!$ReplyType || $ReplyType == 'Category') && val('ReplyTo', $Data)) {
            // This may be replying to the SourceID rather than the UID.
            $SaveType = $this->saveTypeFromRepyTo($Data);
        }

        trace("Reply type: $ReplyType, Reply id: $ReplyID");

        if (strcasecmp($ReplyType, 'noreply') == 0) {
            return true;
        }

        // Save the full post for debugging.
        $Data['Attributes'] = dbencode(arrayTranslate($Data, ['Headers', 'Source']));

        $Data['Body'] = self::stripEmail($Data['Body']);
        if (!$Data['Body']) {
            $Data['Body'] = t('(empty message)');
        }

        list($FromName, $FromEmail) = self::parseEmailAddress($Data['From']);

        // Check for a category.
        if ($ReplyType == 'Category') {
            $CategoryID = $ReplyID;
        } else {
            $CategoryID = c('Plugins.VanillaPop.DefaultCategoryID', -1);
        }
        if (!$CategoryID) {
            $CategoryID = -1;
        }
        touchValue('CategoryID', $Data, $CategoryID);

        // See if there is a user at the given email.
        $UserModel = new UserModel();
        $User = $UserModel->getByEmail($FromEmail);
        if (!$User) {
            if (c('Plugins.VanillaPop.AllowUserRegistration')) {
                saveToConfig('Garden.Registration.NameUnique', false, false);
                $Sender->Data['_Status'][] = 'Creating user.';
                $User = [
                    'Name' => $FromName,
                    'Email' => $FromEmail,
                    'Password' => randomString(10),
                    'HashMethod' => 'Random',
                    'Source' => 'Email',
                    'SourceID' => $FromEmail
                ];

                $UserID = $UserModel->insertForBasic($User, false, ['NoConfirmEmail' => 'NoConfirmEmail']);

                if (!$UserID) {
                    throw new Exception(T('Error creating user.').' '.$UserModel->Validation->resultsText(), 400);
                }

                $User['UserID'] = $UserID;
            } else {
                $this->sendEmail($FromEmail, '',
                    t("Whoops! You'll need to register before you can email our site."), $Data);
                return true;
            }
        } else {
            $Sender->Data['_Status'][] = 'User exists';
            $User = (array)$User;
        }
        Gdn::session()->start($User['UserID'], false);
        $Data['InsertUserID'] = $User['UserID'];

        // Get the parent record and make sure the post is going in the right place.
        if (!isset($SaveType)) {
            switch ($ReplyType) {
                case 'Discussion':
                    // Grab the discussion.
                    $DiscussionModel = new DiscussionModel();
                    $Discussion = $DiscussionModel->getID($ReplyID);
                    if (!$Discussion) {
                        $InvalidReply = true;
                        $SaveType = 'Discussion';
                    } else {
                        $SaveType = 'Comment';
                        $Data['DiscussionID'] = $ReplyID;
                        $Data['CategoryID'] = val('CategoryID', $Discussion);
                    }

                    break;
                case 'Comment':
                    $CommentModel = new CommentModel();
                    $Comment = $CommentModel->getID($ReplyID, DATASET_TYPE_ARRAY);
                    if (!$Comment) {
                        $InvalidReply = true;
                        $SaveType = 'Discussion';
                    } else {
                        // Grab the discussion so we can see its category.
                        $DiscussionModel = new DiscussionModel();
                        $Discussion = $DiscussionModel->getID($Comment['DiscussionID'], DATASET_TYPE_ARRAY);
                        $Data['CategoryID'] = val('CategoryID', $Discussion);

                        $SaveType = 'Comment';
                        $Data['DiscussionID'] = $Comment['DiscussionID'];
                    }
                    break;
                case 'Conversation':
                    $ConversationModel = new ConversationModel();
                    $Conversation = $ConversationModel->getID($ReplyID);
                    if (!$Conversation) {
                        $InvalidReply = true;
                        $SaveType = 'Discussion';
                    } else {
                        // TODO: Check permission.

                        $SaveType = 'Message';
                        $Data['ConversationID'] = $Conversation['ConversationID'];
                    }

                    break;
                case 'Message':
                    $MessageModel = new ConversationMessageModel();
                    $Message = $MessageModel->getID($ReplyID, DATASET_TYPE_ARRAY);
                    if (!$Message) {
                        $InvalidReply = true;
                        $SaveType = 'Discussion';
                    } else {
                        // TODO: Check permission.
                        $SaveType = 'Message';
                        $Data['ConversationID'] = $Message['ConversationID'];
                    }
                    break;
                default:
                    $SaveType = 'Discussion';
                    break;
            }
        }

        if (isset($InvalidReply)) {
            $Data['Body'] .= "\n\n".sprintf(t('Note: The email was trying to reply to an invalid %s.'), "$ReplyType ($ReplyID)");
        }

        // Set the source of the post.
        $Data['Source'] = 'Email';
        $Data['SourceID'] = val('MessageID', $Data, null);
        unset($Data['MessageID']);

        $Category = CategoryModel::categories(val('CategoryID', $Data));
        if ($Category) {
            $PermissionCategoryID = $Category['PermissionCategoryID'];
        } else {
            $PermissionCategoryID = -1;
        }

        trace("Save type: {$SaveType}, Permission Category ID: {$PermissionCategoryID}");

        switch ($SaveType) {
            case 'Comment':
                if (!Gdn::session()->checkPermission('Email.Comments.Add')) {
                    trace("Doesn't have Email.Comments.Add");

                    $this->sendEmail($FromEmail, '',
                        t("Sorry! You don't have permission to comment through email."), $Data);
                    return true;
                } elseif (!Gdn::session()->checkPermission('Vanilla.Comments.Add', true, 'Category', $PermissionCategoryID)) {
                    trace("Doesn't have Vanilla.Comments.Add for category", TRACE_WARNING);

                    $this->sendEmail($FromEmail, '',
                        t("Sorry! You don't have permission to post right now."), $Data);
                    return true;
                } elseif (val('Closed', $Discussion)) {
                    $this->sendEmail($FromEmail, '',
                        t("Sorry! This discussion has been closed."), $Data);
                    return true;
                }

                $CommentModel = new CommentModel();

                // Make sure there isn't already a comment saved from this email.
                if ($Data['SourceID']) {
                    $ExistingComment = $CommentModel->getWhere(['Source' => 'Email', 'SourceID' => $Data['SourceID']])->firstRow();
                    if ($ExistingComment) {
                        trace("This email has already been saved.");
                        return true;
                    }
                }

                $CommentID = $CommentModel->save($Data);
                if (!$CommentID) {
                    throw new Exception($CommentModel->Validation->resultsText().print_r($Data, true), 400);
                } else {
                    $CommentModel->Save2($CommentID, true);
                }
                trace("Saved comment $CommentID");
                return $CommentID;
            case 'Message':
                if (!Gdn::session()->checkPermission('Email.Conversations.Add')) {
                    $this->sendEmail($FromEmail, '',
                        t("Sorry! You don't have permission to send messages through email."), $Data);
                    return true;
                }

                $MessageModel = new ConversationMessageModel();
                $MessageID = $MessageModel->save($Data);
                if (!$MessageID) {
                    throw new Exception($MessageModel->Validation->resultsText().print_r($Data, true), 400);
                }
                return $MessageID;
            case 'Discussion':
            default:
                // Check the permission on the discussion.
                if (!Gdn::session()->checkPermission('Email.Discussions.Add')) {
                    trace("Doesn't have Email.Discussions.Add");

                    $this->sendEmail($FromEmail, '',
                        t("Sorry! You don't have permission to post discussions/questions through email."), $Data);
                    return true;
                } elseif (!Gdn::session()->checkPermission('Vanilla.Discussions.Add', true, 'Category', $PermissionCategoryID)) {
                    trace("Sorry! You don't have permission to post right now.", TRACE_WARNING);

                    $this->sendEmail($FromEmail, '',
                        t("Sorry! You don't have permission to post right now."), $Data);
                    return true;
                }

                $Data['Name'] = $Data['Subject'];
                $Data['UpdateUserID'] = $Data['InsertUserID'];
                $DiscussionModel = new DiscussionModel();
                $DiscussionID = $DiscussionModel->save($Data);
                if (!$DiscussionID) {
                    throw new Exception($DiscussionModel->Validation->resultsText().print_r($Data, true), 400);
                }
                trace("Saved discussion $DiscussionID");

                // Send a confirmation email.
                if (c('Plugins.VanillaPop.SendConfirmationEmail')) {
                    $Data['DiscussionID'] = $DiscussionID;
                    $this->sendConfirmationEmail($Data, $User);
                }

                return $DiscussionID;
        }
    }

    public function saveTypeFromRepyTo(&$Data) {
        $Tables = [
            'Discussion' => ['Comment', 'DiscussionID'],
            'Comment' => ['Comment', 'DiscussionID'],
            'ConversationMessage' => ['Message', 'ConversationID']];

        $ReplyTo = trim(val('ReplyTo', $Data));
        if (!$ReplyTo) {
            return null;
        }

        foreach ($Tables as $Name => $Info) {
            $Row = Gdn::sql()->getWhere($Name, ['Source' => 'Email', 'SourceID' => $ReplyTo])->firstRow(DATASET_TYPE_ARRAY);
            if ($Row) {
                $Result = $Info[0];
                $Data[$Info[1]] = $Row[$Info[1]];
                $Data['ParentID'] = $Row[$Info[1]];
                return $Result;
            }
        }
        return null;
    }

    public function sendEmail($To, $Subject, $Body, $Quote = false) {
        trace("Email: $Body");

        $Email = new Gdn_Email();
        $Email->to($To);
        $Email->subject(sprintf('[%s] %s', c('Garden.Title'), $Subject));
        $From = $Email->PhpMailer->From;
        $Email->PhpMailer->From = self::addIDToEmail($From, 'noreply');

        if (is_array($Quote)) {
            $MessageID = val('MessageID', $Quote);
            if ($MessageID) {
                $Email->PhpMailer->addCustomHeader("In-Reply-To:$MessageID");
                $Email->PhpMailer->addCustomHeader("References:$MessageID");
            }

            $Subject = val('Subject', $Quote);
            if ($Subject) {
                $Email->subject(sprintf('Re: [%s] %s', c('Garden.Title'), ltrim(stringBeginsWith($Subject, 'Re:', true, true))));
            }
        }

        $Message = self::formatEmailBody($Body, false, $Quote);

        $Email->message($Message);
        @$Email->send();
    }

    /**
     * Set the from address to the name of the user that sent the notification.
     *
     * @param Gdn_Email $PhpMailer
     * @param int|array
     */
    public function setFrom($Email, $User) {
        if (!C('Plugins.VanillaPop.OverrideFrom', true)) {
            return;
        }

        if (is_numeric($User)) {
            $User = Gdn::userModel()->getID($User);
        }

        $Email->PhpMailer->FromName = val('Name', $User);
    }

    /**
     * Send the initial confirmation email when a discussion is first started through email.
     *
     * @param type $Discussion
     * @param type $User
     */
    public function sendConfirmationEmail($Discussion, $User) {
        $FormatData = $Discussion;
        $FormatData['Title'] = c('Garden.Title');
        $FormatData['ID'] = $Discussion['DiscussionID'];
        $FormatData['Category'] = CategoryModel::categories($Discussion['CategoryID']);
        $FormatData['Url'] = externalUrl('/discussion/'.$Discussion['DiscussionID'].'/'.Gdn_Format::url($Discussion['Name']));

        $FormatData['Quote'] = self::formatQuoteText($FormatData['Body']);

        $CanView = Gdn::userModel()->getCategoryViewPermission($User['UserID'], val('CategoryID', $Discussion));
        $CanReply = self::checkUserPermission($User['UserID'], 'Email.Comments.Add');
        $Route = '/discussion/'.$Discussion['DiscussionID'].'/'.Gdn_Format::url($Discussion['Name']);
        $FormatData['Signature'] = self::emailSignature($Route, $CanView, $CanReply);

        $Email = new Gdn_Email();

        $Message = formatString(C('EmailFormat.ConfirmationBody', self::$FormatDefaults['ConfirmationBody']), $FormatData);
        $Email->message($Message);

        // We are using the standard confirmation subject because some email clients won't group emails unless their subject are the exact same.
        $Subject = formatString(C('EmailFormat.DiscussionSubject', self::$FormatDefaults['DiscussionSubject']), $FormatData);
        $Email->subject($Subject);

        $Email->PhpMailer->MessageID = self::uid('Discussion', $Discussion['DiscussionID'], 'email');
        $Email->PhpMailer->From = self::addIDToEmail($Email->PhpMailer->From, self::uid('Discussion', $Discussion['DiscussionID']));
        $Email->to($User['Email'], $User['Name']);

        $ReplyTo = val('SourceID', $Discussion);
        if (isset($ReplyTo)) {
            $Email->PhpMailer->addCustomHeader("In-Reply-To:$ReplyTo");
            $Email->PhpMailer->addCustomHeader("References:$ReplyTo");
        }


        try {
            $Email->send();
        } catch (Exception $Ex) {
            // Do nothing for now...
            if (debug()) {
                throw $Ex;
            }
        }
    }

    public function setup() {
        $this->structure();
    }

    public function structure() {
        Gdn::permissionModel()->define([
            'Email.Discussions.Add' => 'Garden.Profiles.Edit',
            'Email.Comments.Add' => 'Garden.Profiles.Edit',
            'Email.Conversations.Add' => 'Garden.Profiles.Edit']);

        Gdn::structure()
            ->table('User')
            ->column('Source', 'varchar(20)', null)
            ->column('SourceID', 'varchar(191)', null, 'index')
            ->set();

        Gdn::structure()
            ->table('Discussion')
            ->column('Source', 'varchar(20)', null)
            ->column('SourceID', 'varchar(191)', null, 'index')
            ->set();

        Gdn::structure()
            ->table('Comment')
            ->column('Source', 'varchar(20)', null)
            ->column('SourceID', 'varchar(191)', null, 'index')
            ->set();

        Gdn::structure()
            ->table('ConversationMessage')
            ->column('Source', 'varchar(20)', null)
            ->column('SourceID', 'varchar(191)', null, 'index')
            ->set();

        Gdn::structure()
            ->table('Role')
            ->column('ForceNotify', 'tinyint(1)', '0')
            ->set();
    }

    public static function simpleForm($Form, $Schema) {
        echo '<ul>';
        foreach ($Schema as $Index => $Row) {
            if (is_string($Row)) {
                $Row = ['Name' => $Index, 'Control' => $Row];
            }

            if (!isset($Row['Name'])) {
                $Row['Name'] = $Index;
            }
            if (!isset($Row['Options'])) {
                $Row['Options'] = [];
            }

            echo "<li>\n  ";

            $LabelCode = self::labelCode($Row);

            $Description = val('Description', $Row, '');
            if ($Description) {
                $Description = '<div class="Info">'.$Description.'</div>';
            }

            touchValue('Control', $Row, 'TextBox');

            switch (strtolower($Row['Control'])) {
                case 'checkbox':
                    echo $Description;
                    echo $Form->checkBox($Row['Name'], t($LabelCode));
                    break;
                case 'dropdown':
                    echo $Form->label($LabelCode, $Row['Name']);
                    echo $Description;
                    echo $Form->dropDown($Row['Name'], $Row['Items'], $Row['Options']);
                    break;
                case 'radiolist':
                    echo $Description;
                    echo $Form->radioList($Row['Name'], $Row['Items'], $Row['Options']);
                    break;
                case 'checkboxlist':
                    echo $Form->label($LabelCode, $Row['Name']);
                    echo $Description;
                    echo $Form->checkBoxList($Row['Name'], $Row['Items'], null, $Row['Options']);
                    break;
                case 'textbox':
                    echo $Form->label($LabelCode, $Row['Name']);
                    echo $Description;
                    echo $Form->textBox($Row['Name'], $Row['Options']);
                    break;
                default:
                    echo "Error a control type of {$Row['Control']} is not supported.";
                    break;
            }
            echo "\n</li>\n";
        }
        echo '</ul>';
    }

    public static function stripSignature($Body) {
        $i = strrpos($Body, "\n--");
        if ($i === false) {
            return $Body;
        }
        $j = strpos($Body, "\n", $i + 1);
        if ($j === false) {
            return $Body;
        }

        $Delim = trim(substr($Body, $i, $j - $i + 1));
        if (preg_match('`^-+$`', $Delim)) {
            $Body = trim(substr($Body, 0, $i));
        }

        return $Body;
    }

    /**
     * Get the current email domain.
     *
     * @return string Returns the email domain to use.
     */
    public function getEmailDomain() {
        $hostname = $this->getSiteHostname();
        list($slug, $tld) = $this->splitHostname($hostname);

        return val($tld, $this->emailDomains, 'noreply.email');
    }

    /**
     * Get the current site hostname.
     *
     * @return string Returns the current site hostname.
     */
    public function getSiteHostname() {
        if (class_exists('Infrastructure')) {
            return (string)Infrastructure::site('name');
        } else {
            return '';
        }
    }

    /**
     * Split a vanilla hostname into its tld and subdomain.
     *
     * @param string $hostname The hostname to split.
     * @return array Returns an array in the form `[$subdomain, $tld]`.
     */
    protected function splitHostname($hostname) {
        $parts = explode('.', $hostname);
        if (count($parts) <= 2) {
            return ['', $hostname];
        } else {
            return [
                implode('.', array_slice($parts, 0, -2)),
                implode('.', array_slice($parts, -2))
            ];
        }
    }

    public static function stripEmail($Body) {
        $SigFound = false;
        $InQuotes = 0;

        $Body = str_replace("\r\n", "\n", $Body);

        $Lines = explode("\n", trim($Body));
        $LastLine = count($Lines);

        for ($i = $LastLine - 1; $i >= 0; $i--) {
            $Line = $Lines[$i];

            if ($InQuotes === 0 && preg_match('`^\s*[>|]`', $Line)) {
                // This is a quote line.
                $LastLine = $i;
            } elseif (!$SigFound && preg_match('`^\s*--`', $Line)) {
                // -- Signature delimiter.
                $LastLine = $i;
                $SigFound = true;
            } elseif (preg_match('`^\s*---.+---\s*$`', $Line)) {
                // This will catch an ------Original Message------ heade
                $LastLine = $i;
                $InQuotes = false;
            } elseif ($InQuotes === 0) {
                if (preg_match('`wrote:\s*$`i', $Line)) {
                    // This is the quote line...
                    $LastLine = $i;
                    $InQuotes = false;

                    $PrevLine = val($i - 1, $Lines);
                    $OnRegex = '`^On\s+`i';
                    if (!preg_match($OnRegex, $Line) && preg_match($OnRegex, $PrevLine)) {
                        $i--;
                        $LastLine = $i;
                    }
                } elseif (preg_match('`^\s*$`', $Line)) {
                    $LastLine = $i;
                } else {
                    $InQuotes = false;
                }
            }
        }

        if ($LastLine >= 1) {
            $Lines = array_slice($Lines, 0, $LastLine);
        }
        $Result = trim(implode("\n", $Lines));
        return $Result;
    }

    /**
     * Generate an email UID from a record.
     *
     * The UID is used when replying to email notifications to know which email to reply to.
     *
     * This method adds the current site ID to the UID at the end so that the email router will know which site the
     * email originated from and be able to send replies to the current site. If VanillaPop isn't running on
     * infrastructure then the site ID will not be added.
     *
     * @param string $type The type of record.
     * @param int $id The record's ID.
     * @param string $format An optional format to apply to the result. This can be an empty string or "email" to format
     * in a way appropriate to add to an email header.
     * @return null|string Returns a UID as a string or **null** of {@link $type} is unknown.
     */
    public static function uid($type, $id, $format = '') {
        $typeKey = val($type, array_flip(self::$Types), null);
        if (!$typeKey) {
            return null;
        }
        $uid = $typeKey.$id;

        // Add the site ID to the end of the UID.
        if (class_exists('Infrastructure')) {
            $uid .= '-s'.Infrastructure::siteID();
        }

        switch (strtolower($format)) {
            case 'email':
                $uid = '<'.$uid.'@'.Gdn::request()->host().'>';
        }
        return $uid;
    }

    /// Event Handlers ///

    /**
     * @param ActivityModel $Sender
     * @param type $Args
     */
    public function activityModel_beforeSendNotification_handler($Sender, $Args) {
        if (isset($Args['RecordType']) && isset($Args['RecordID'])) {
            $Type = $Args['RecordType'];
            $ID = $Args['RecordID'];
        } else {
            list($Type, $ID) = self::parseRoute(val('Route', $Args));
        }

        $FormatData = ['Title' => c('Garden.Title'), 'Signature' => self::emailSignature(val('Route', $Args))];
        $NotifyUserID = getValueR('Activity.NotifyUserID', $Args);

        if (in_array($Type, ['Discussion', 'Comment', 'Conversation', 'Message'])) {
            $Email = $Args['Email']; //new Gdn_Email(); //
            $Story = val('Story', $Args);

            switch ($Type) {
                case 'Discussion':
                    $DiscussionModel = new DiscussionModel();
                    $Discussion = $DiscussionModel->getID($ID);
                    if ($Discussion) {
                        // See if the user has permission to view this discussion on the site.
                        $CanView = Gdn::userModel()->getCategoryViewPermission($NotifyUserID, val('CategoryID', $Discussion));
                        $CanReply = self::checkUserPermission($NotifyUserID, 'Email.Comments.Add');
                        $FormatData['Signature'] = self::emailSignature(val('Route', $Args), $CanView, $CanReply);

                        $Discussion = (array)$Discussion;
                        $Discussion['Name'] = Gdn_Format::plainText($Discussion['Name'], 'Text');

                        $Body = Gdn_Format::to($Discussion['Body'], $Discussion['Format']);
                        $Body = $this->transformQuotes($Body);
                        $Body = Gdn_Format::plainText($Body, 'html', true);
                        $Body = $this->parseQuotes($Body);

                        $Discussion['Body'] = $Body;
                        $Discussion['Category'] = CategoryModel::categories($Discussion['CategoryID']);
                        $Discussion['Url'] = externalUrl('/discussion/'.$Discussion['DiscussionID'].'/'.Gdn_Format::url($Discussion['Name']));
                        $FormatData = array_merge($FormatData, $Discussion);

                        $Message = formatString(C('EmailFormat.DiscussionBody', self::$FormatDefaults['DiscussionBody']), $FormatData);
                        $Email->message($Message);

                        $Subject = formatString(C('EmailFormat.DiscussionSubject', self::$FormatDefaults['DiscussionSubject']), $FormatData);
                        $Email->subject($Subject);

                        $this->setFrom($Email, $Discussion['InsertUserID']);
                        $Email->PhpMailer->From = self::addIDToEmail($Email->PhpMailer->From, self::uid('Discussion', val('DiscussionID', $Discussion)));
                    }
                    break;
                case 'Comment':
                    $CommentModel = new CommentModel();
                    $Comment = $CommentModel->getID($ID, DATASET_TYPE_ARRAY);

                    if ($Comment) {
                        $Body = Gdn_Format::to($Comment['Body'], $Comment['Format']);
                        $Body = $this->transformQuotes($Body);
                        $Body = Gdn_Format::plainText($Body, 'html', true);
                        $Body = $this->parseQuotes($Body);

                        $Comment['Body'] = $Body;
                        $Comment['Url'] = externalUrl(val('Route', $Args));
                        $Comment = [$Comment];
                        Gdn::userModel()->joinUsers($Comment, ['InsertUserID', 'UpdateUserID']);
                        $Comment = $Comment[0];

                        if (in_array(getValueR('Activity.ActivityType', $Args), ['AnswerAccepted'])) {
                            $Comment['Body'] = Gdn_Format::plainText($Args['Headline'], 'Html')."\n\n".$Comment['Body'];
                        }

                        $FormatData = array_merge($FormatData, $Comment);

                        $this->setFrom($Email, $Comment['InsertUserID']);

                        $DiscussionModel = new DiscussionModel();
                        $Discussion = (array)$DiscussionModel->getID($Comment['DiscussionID']);

                        if ($Discussion) {
                            // See if the user has permission to view this discussion on the site.
                            $CanView = Gdn::userModel()->getCategoryViewPermission($NotifyUserID, val('CategoryID', $Discussion));
                            $CanReply = self::checkUserPermission($NotifyUserID, 'Email.Comments.Add');
                            $FormatData['Signature'] = self::emailSignature(val('Route', $Args), $CanView, $CanReply); //.print_r(array('CanView' => $CanView, 'CanReply' => $CanReply), true);

                            $Discussion['Name'] = Gdn_Format::plainText($Discussion['Name'], 'Text');
                            $Discussion['Body'] = Gdn_Format::plainText($Discussion['Body'], $Discussion['Format']);
                            $Discussion['Url'] = externalUrl('/discussion/'.$Discussion['DiscussionID'].'/'.Gdn_Format::url($Discussion['Name']));
                            $FormatData['Discussion'] = $Discussion;
                            $FormatData['Category'] = CategoryModel::categories($Discussion['CategoryID']);

                            $Message = formatString(C('EmailFormat.CommentBody', self::$FormatDefaults['CommentBody']), $FormatData);
                            $Email->message($Message);

                            $Subject = formatString(C('EmailFormat.CommentSubject', self::$FormatDefaults['CommentSubject']), $FormatData);
                            $Email->subject($Subject);

                            $Source = val('Source', $Discussion);
                            if ($Source == 'Email') {
                                 // replying to an email...
                                $ReplyTo = val('SourceID', $Discussion);
                            }
                            else {
                                $ReplyTo = self::uid('Discussion', val('DiscussionID', $Discussion), 'email');
                            }

                            $Email->PhpMailer->From = self::addIDToEmail($Email->PhpMailer->From, self::uid('Discussion', val('DiscussionID', $Discussion)));
                        }
                    }

                    break;
                case 'Message':
                    // Get this message.
                    $Message = Gdn::sql()->getWhere('ConversationMessage', ['MessageID' => $ID])->firstRow(DATASET_TYPE_ARRAY);
                    if ($Message) {
                        $ConversationID = $Message['ConversationID'];
                        $this->setFrom($Email, $Message['InsertUserID']);

                        // Get the message before this one.
                        $Message2 = Gdn::sql()
                            ->select('*')
                            ->from('ConversationMessage')
                            ->where('ConversationID', $ConversationID)
                            ->where('MessageID <', $ID)
                            ->orderBy('MessageID', 'desc')
                            ->limit(1)
                            ->get()->firstRow(DATASET_TYPE_ARRAY);

                        if ($Message2) {
                            if ($Message2['Source'] == 'Email') {
                                $ReplyTo = $Message2['SourceID'];
                            } else {
                                $ReplyTo = self::uid('Message', $Message2['MessageID'], 'email');
                            }
                        }

                        $Email->PhpMailer->From = self::addIDToEmail($Email->PhpMailer->From, self::uid('Message', val('MessageID', $Message)));
                    }

                    // See if the user has permission to view this discussion on the site.
                    $CanView = true;
                    $CanReply = self::checkUserPermission($NotifyUserID, 'Email.Conversations.Add');
                    $FormatData['Signature'] = self::emailSignature(val('Route', $Args), $CanView, $CanReply);

                    $Message = Gdn_Format::to($Message['Body'], $Message['Format']);
                    $Message = $this->transformQuotes($Message);
                    $Message = Gdn_Format::plainText($Message, 'html', true);
                    $Message = $this->parseQuotes($Message);
                    $Message .= "\n\n-- \n".$FormatData['Signature'];
                    $Email->message($Message);

                    break;
            }
            if (isset($ReplyTo)) {
                $Email->PhpMailer->addCustomHeader("In-Reply-To:$ReplyTo");
                $Email->PhpMailer->addCustomHeader("References:$ReplyTo");
            }
            $Email->PhpMailer->MessageID = self::uid($Type, $ID, 'email');
        }
    }

    /**
     * Add notifications.
     */
    public function commentModel_beforeNotification_handler($Sender, $Args) {
        // Make sure the discussion's user is notified if they started the discussion by email.
        if (getValueR('Discussion.Source', $Args) == 'Email') {
            $NotifiedUsers = (array)val('NotifiedUsers', $Args);
            $InsertUserID = getValueR('Discussion.InsertUserID', $Args);

            // Construct an activity and send it.
            $ActivityModel = $Args['ActivityModel'];

            $Comment = $Args['Comment'];
            $CommentID = $Comment['CommentID'];
            $HeadlineFormat = t('HeadlineFormat.Comment', '{ActivityUserID,user} commented on <a href="{Url,html}">{Data.Name,text}</a>');

            $Activity = [
                'ActivityType' => 'Comment',
                'ActivityUserID' => $Comment['InsertUserID'],
                'NotifyUserID' => $InsertUserID,
                'HeadlineFormat' => $HeadlineFormat,
                'RecordType' => 'Comment',
                'RecordID' => $CommentID,
                'Route' => "/discussion/comment/$CommentID#Comment_$CommentID",
                'Data' => ['Name' => val('Name', $Args['Discussion'])],
                'Notified' => ActivityModel::SENT_OK,
                'Emailed' => ActivityModel::SENT_PENDING
            ];

            $ActivityModel->queue($Activity, false, ['Force' => true]);
        }

        // Notify anyone in a ForceNotify role
        $this->forceNotify($Sender, $Args);
    }

    /**
     * Add notifications.
     */
    public function discussionModel_beforeNotification_handler($Sender, $Args) {
        // Notify anyone in a ForceNotify role
        $this->forceNotify($Sender, $Args);
    }

//   public function discussionController_afterCommentBody_handler($Sender, $Args) {
//      $Attributes = getValueR('Object.Attributes', $Args);
//      if (is_string($Attributes)) {
//         $Attributes = @unserialize($Attributes);
//      }
//
//      $Body = getValueR('Object.Body', $Args);
//      $Format = getValueR('Object.Format', $Args);
//      $Text = self::formatPlainText($Body, $Format);
//
//      $Source = val('Source', $Attributes, false);
//      if (is_array($Source))
//         echo '<pre>'.htmlspecialchars(val("Headers", $Attributes), $Source).'</pre>';
//   }

    public function gdn_Dispatcher_BeforeBlockDetect_Handler($Sender, $Args) {
        $Args['BlockExceptions']['`post/sendgrid(\/.*)?$`'] = Gdn_Dispatcher::BLOCK_NEVER;
    }

    public function postController_email_create($Sender, $Args = []) {
        $this->utilityController_Email_Create($Sender, $Args);
    }

    public function utilityController_email_create($Sender, $Args = []) {
        if (Gdn::session()->UserID == 0) {
            Gdn::session()->start(Gdn::userModel()->getSystemUserID(), false);
            Gdn::session()->User->Admin = false;
        }

        if ($Sender->Form->isPostBack()) {
            $Data = $Sender->Form->formValues();
            trace('Saving data.');
            if ($this->save($Data, $Sender)) {
                $Sender->StatusMessage = t('Saved');
                $Sender->setData('Saved', true);
                $Sender->setData('Trace', trace());
            }
        }

        $Sender->setData('Title', t('Post an Email'));
        $Sender->render('Email', '', 'plugins/VanillaPop');
    }

    public function postController_sendgrid_create($Sender, $Args = []) {
        $this->utilityController_Sendgrid_Create($Sender, $Args);
    }

    /**
     *
     * @param PostController $Sender
     * @param array $Args
     */
    public function utilityController_sendgrid_create($Sender, $Args = []) {
        try {
            Gdn::session()->start(Gdn::userModel()->getSystemUserID(), false);
            Gdn::session()->User->Admin = false;

            if ($Sender->Form->isPostBack()) {
                self::log("Postback");

                self::log("Getting post...");
                $Post = $Sender->Form->formValues();
                self::log("Post got...");
                $Data = arrayTranslate($Post, [
                    'from' => 'From',
                    'to' => 'To',
                    'subject' => 'Subject'
                ]);

                //         self::log('Parsing headers.'.val('headers', $Post, ''));
                $Headers = self::parseEmailHeader(val('headers', $Post, ''));
                //         self::log('Headers: '.print_r($Headers, true));
                $Headers = array_change_key_case($Headers);
                $HeaderData = arrayTranslate($Headers, ['message-id' => 'MessageID', 'references' => 'References', 'in-reply-to' => 'ReplyTo']);
                $Data = array_merge($Data, $HeaderData);

                if (false && val('html', $Post)) {
                    $Data['Body'] = $Post['html'];
                    $Data['Format'] = 'Html';
                } else {
                    $Data['Body'] = $Post['text'];
                    $Data['Format'] = 'Html';
                }

                self::log("Saving data...");
                $Sender->Data['_Status'][] = 'Saving data.';


                if ($this->save($Data, $Sender)) {
                    $Sender->StatusMessage = t('Saved');
                } else {
                    throw new Exception('Could not save...', 400);
                }
            }

            $Sender->setData('Title', t('Sendgrid Proxy'));
            $Sender->render('Sendgrid', '', 'plugins/VanillaPop');
        } catch (Exception $Ex) {
            $Contents = $Ex->getMessage()."\n"
                .$Ex->getTraceAsString()."\n"
                .print_r($_POST, true);
            file_put_contents(PATH_UPLOADS.'/email/error_'.time().'.txt', $Contents);

            throw $Ex;
        }
    }

    /**
     *
     * @param SettingsController $sender
     * @param array $args
     */
    public function settingsController_vanillaPop_create($sender, $args = []) {
        $sender->permission('Garden.Settings.Manage');

        $ConfSettings = [
            'Plugins.VanillaPop.DefaultCategoryID' => ['Control' => 'CategoryDropDown', 'Description' => 'Place discussions started through email in the following category.'],
            'Plugins.VanillaPop.AllowUserRegistration' => ['Control' => 'CheckBox', 'LabelCode' => 'Allow new users to be registered through email.'],
            'Plugins.VanillaPop.AugmentFrom' => ['Control' => 'CheckBox', 'LabelCode' => 'Add information into the from field in email addresses to help with replies (recommended).', 'Default' => true],
            'Garden.Email.SupportAddress' => ['Control' => 'TextBox', 'LabelCode' => 'Outgoing Email Address', 'Description' => 'This is the address that will show up in the from field of emails sent from the application.'],
            'EmailFormat.DiscussionSubject' => ['Control' => 'TextBox'],
            'EmailFormat.DiscussionBody' => [],
            'EmailFormat.CommentSubject' => [],
            'EmailFormat.CommentBody' => [],
            'Plugins.VanillaPop.SendConfirmationEmail' => ['Control' => 'CheckBox', 'LabelCode' => 'Send a confirmation email when people ask a question or start a discussion over email.'],
            'EmailFormat.ConfirmationBody' => [],
            'Plugins.VanillaPop.AllowForceNotify' => ['Control' => 'CheckBox', 'LabelCode' => 'Allow roles to be configured to force email notifications to users.'],

        ];

        foreach (self::$FormatDefaults as $Name => $Default) {
            $Options = val('Options', $ConfSettings['EmailFormat.'.$Name], []);
            if (stringEndsWith($Name, 'Body')) {
                $Options['Multiline'] = true;
            }
            $ConfSettings['EmailFormat.'.$Name] = ['Control' => 'TextBox', 'Default' => $Default, 'Options' => $Options];
        }

        $Conf = new ConfigurationModule($sender);
        $Conf->initialize($ConfSettings);

        $emailDomain = $this->getEmailDomain();
        list($slug, $tld) = $this->splitHostname($this->getSiteHostname());

        // Correct the slug for the site hub format.
        if ($nodeSlug = val('NODE_SLUG', $_SERVER)) {
            $slug = $nodeSlug.'.'.rtrim(stringEndsWith($slug, $nodeSlug, true, true), '-');
        }

        if ($emailDomain && $slug) {
            $sender->setData('IncomingAddress', "$slug@$emailDomain");
            if (!empty($nodeSlug) || strpos($slug, '.') === false) {
                $sender->setData('CategoryAddress', "categorycode.$slug@$emailDomain");
            } else {
                $sender->setData('CategoryAddress', "$slug+categorycode@$emailDomain");
            }
        }

        $sender->addSideMenu();
        $sender->setData('Title', t('Incoming Email'));
        $sender->ConfigurationModule = $Conf;
//      $Conf->renderAll();
        $sender->render('Settings', '', 'plugins/VanillaPop');
    }

    /**
     * Allow roles to be configured to force email notifications.
     */
    public function base_beforeRolePermissions_handler($Sender) {
        if (!C('Plugins.VanillaPop.AllowForceNotify')) {
            return;
        }

        $NotifyOptions = [
            0 => 'Notify these users normally using their preferences (recommended)',
            1 => 'Notify these users for every new comment and discussion',
            2 => 'Notify these users for new announcements'
        ];

        $Sender->Data['_ExtendedFields']['ForceNotify'] = [
            'LabelCode' => 'Notifications Override',
            'Control' => 'DropDown',
            'Items' => $NotifyOptions
        ];

    }

    /**
     * Send forced email notifications.
     */
    public function forceNotify($Sender, $Args) {
        if (!C('Plugins.VanillaPop.AllowForceNotify')) {
            return;
        }

        $Activity = $Args['Activity'];
        $ActivityModel = $Args['ActivityModel'];
        $ActivityType = (isset($Args['Comment'])) ? 'Comment' : 'Discussion';
        $Fields = $Args[$ActivityType];

        // Email them.
        $Activity['Emailed'] = ActivityModel::SENT_PENDING;

        // Get effected roles.
        $RoleModel = new RoleModel();
        $RoleIDs = [];
        if ($ActivityType == 'Discussion' && val('Announce', $Args['Discussion'])) {
            // Add everyone with force notify all OR announcement-only option.
            $Wheres = ['ForceNotify >' => 0];
        } else {
            // Only get users with force notify all.
            $Wheres = ['ForceNotify' => 1];
        }

        $Roles = $RoleModel->getWhere($Wheres)->resultArray();
        foreach ($Roles as $Role) {
            $RoleIDs[] = val('RoleID', $Role);
        }

        // Get users in those roles.
        $UserRoles = $Sender->SQL
            ->select('UserID')
            ->distinct()
            ->from('UserRole')
            ->whereIn('RoleID', $RoleIDs)
            ->get()->resultArray();


        // Add an activity for each person and pray we don't melt the wibbles.
        foreach ($UserRoles as $UserRole) {
            $Activity['NotifyUserID'] = $UserRole['UserID'];
            $ActivityModel->queue($Activity, false, ['Force' => true]);
        }
    }

    /**
     * Replace html quotes by custom markup. Used to evade plain text conversion.
     *
     * @param string $htmlContent
     * @return string
     */
    private function transformQuotes($htmlContent) {
        $i = 0;
        // Replace all blockquotes with no other blockquote as a child, one at the time (starting by the last one)!
        while (preg_match('/\n?<blockquote[^>]*>(?!.*<blockquote[^>]*>)(.+?)<\/blockquote>/is', $htmlContent, $matches)) {
            $htmlContent = str_replace($matches[0], '{#QUOTE_BEGIN#}'.trim($matches[1]).'{#QUOTE_END#}', $htmlContent);
            if ($i++ > 1000) {
                break; // The parsing went wrong :)
            }
        }

        return $htmlContent;
    }

    /**
     * Format markup generated by transformQuotes.
     *
     * @param $text
     * @return mixed
     */
    private function parseQuotes($text) {
        $i = 0;
        /*
         * Make sure that we have a line return after {#QUOTE_END#}.
         * This is necessary for the explode to work properly!
         */
        $text = preg_replace('/{#QUOTE_END#}(\s*)/', "{#QUOTE_END#}\n\n", $text);
        // Make sure there are not too much spaces/line returns after an user name.
        $text = preg_replace('/ said:(\s*)/', " said:\n\n", $text);

        while (preg_match('/\s*{#QUOTE_BEGIN#}(?!.*{#QUOTE_BEGIN#})(.+?){#QUOTE_END#}/is', $text, $matches)) {
            $indented = "\n\n> ".implode("\n> ", explode("\n", trim($matches[1])));
            $text = str_replace($matches[0], $indented, $text);
            if ($i++ > 1000) {
                break; // The parsing went wrong :)
            }
        }

        return trim($text);
    }

}
