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
        'vanillacommunities.com' => 'vanillacommunities.email',
        'vanillastaging.com' => 'vanillastaging.email',
        'vanillawip.com' => 'dev.vanillaforums.email'
    ];


    /// Methods ///

    public static function addIDToEmail($email, $iD) {
        // Encode the message ID in the from.
        if (c('Plugins.VanillaPop.AugmentFrom', true)) {
            $fromParts = explode('@', $email, 2);
            if (count($fromParts) == 2) {
                $email = "{$fromParts[0]}+$iD@{$fromParts[1]}";
            }
        }
        return $email;
    }

    public static function checkUserPermission($userID, $permission) {
        $permissions = Gdn::userModel()->definePermissions($userID, false);
        $result = in_array($permission, $permissions) || array_key_exists($permission, $permissions);
        return $result;
    }

//   public static function formatPlainText($Body, $Format) {
//      $Result = Gdn_Format::to($Body, $Format);
//
//      if ($Format != 'Text')
//         $Result = Gdn_Format::text($Result, false);
//      $Result = trim(html_entity_decode($Result, ENT_QUOTES, 'UTF-8'));
//      return $Result;
//   }

    public static function formatEmailBody($body, $route = '', $quote = '', $options = false) {
        // Construct the signature.
        if ($route) {
            $signature = formatString(t('ReplyOrFollow'))."\n".externalUrl($route);
        } elseif ($route === false) {
            $signature = externalUrl('/');
        } else {
            $signature = formatString(t('ReplyOnly'));
        }

        if ($quote) {
            if (is_array($quote)) {
                $quote = Gdn_Format::plainText($quote['Body'], val('Format', $quote, 'Text'));
            }

            $quote = "\n\n".t('You wrote:')."\n\n".self::formatQuoteText($quote);
        }

        $result = formatString(t('EmailTemplate'), ['Body' => $body, 'Signature' => $signature, 'Quote' => $quote]);
        return $result;
    }

    public static function emailSignature($route = '', $canView = true, $canReply = true) {
        if (!$route) {
            $canView = false;
        }

        if ($canView && $canReply) {
            $signature = formatString(t('ReplyOrFollow'))."\n".externalUrl($route);
        } elseif ($canView) {
            $signature = formatString(t('FollowOnly'))."\n".externalUrl($route);
        } elseif ($canReply) {
            $signature = formatString(t('ReplyOnly'));
        } else {
            $signature = externalUrl('/');
        }
        return $signature;
    }

    public static function formatQuoteText($text) {
        $result = '> '.str_replace("\n", "\n> ", $text);
        return $result;
    }

    public static function labelCode($schemaRow) {
        if (isset($schemaRow['LabelCode'])) {
            return $schemaRow['LabelCode'];
        }

        $labelCode = $schemaRow['Name'];
        if (strpos($labelCode, '.') !== false) {
            $labelCode = trim(strrchr($labelCode, '.'), '.');
        }

        // Split camel case labels into seperate words.
        $labelCode = preg_replace('`(?<![A-Z0-9])([A-Z0-9])`', ' $1', $labelCode);
        $labelCode = preg_replace('`([A-Z0-9])(?=[a-z])`', ' $1', $labelCode);
        $labelCode = trim($labelCode);

        return $labelCode;
    }

    public static function log($message) {
//      $Line = Gdn_Format::toDateTime().' '.$Message."\n";
//      file_put_contents(PATH_UPLOADS.'/email/log.txt', $Line, FILE_APPEND);
    }

    public static function parseEmailAddress($email) {
        $name = '';
        if (preg_match('`([^<]*)<([^>]+)>`', $email, $matches)) {
            $name = trim(trim($matches[1]), '"');
            $email = trim($matches[2]);
        }

        if (!$name) {
            $name = trim(substr($email, 0, strpos($email, '@')), '@');

            $nameParts = explode('.', $name);
            $nameParts = array_map('ucfirst', $nameParts);
            $name = implode(' ', $nameParts);
        }

        $result = [$name, $email];
        return $result;
    }

    public static function parseEmailHeader($header) {
        $result = [];
        $parts = explode("\n", $header);

        $i = null;
        foreach ($parts as $part) {
            if (!$part) {
                continue;
            }
            if (preg_match('`^\s`', $part)) {
                if (isset($result[$i])) {
                    $result[$i] .= "\n".$part;
                }
            } else {
                self::log("Headerline: $part");
                list($name, $value) = explode(':', $part, 2);
                $i = trim($name);
                $result[$i] = ltrim($value);
            }
        }

        return $result;
    }

    public static function parseRoute($route) {
        if (preg_match('`/?(?:vanilla/)?discussion/(\d+)`i', $route, $matches)) {
            $type = 'Discussion';
            $iD = $matches[1];
        } elseif (preg_match('`/?(?:vanilla/)?discussion/comment/(\d+)`i', $route, $matches)) {
            $type = 'Comment';
            $iD = $matches[1];
        } elseif (preg_match('`/?(?:conversations/)?messages/\d+#(?:Message_)?(\d+)`i', $route, $matches)) {
            $type = 'Message';
            $iD = $matches[1];
        } else {
            return [null, null];
        }

        return [$type, $iD];
    }

    public static function parseType($email) {
        $type = null;
        $iD = null;
        if (preg_match('`\+([a-z]+-?[0-9]+)@`', $email, $matches)) {
            list($type, $iD) = self::parseUID($matches[1]);
        } elseif (preg_match('`\+noreply@`i', $email, $matches)) {
            $type = 'noreply';
            $iD = null;
        } else {
            // See if there is a category in the email address.
            $parts = explode('@', $email);
            $codes = explode('.', $parts[0]);

            if (count($codes) > 0) {
                $category = CategoryModel::categories($codes[0]);
                if ($category) {
                    $type = 'Category';
                    $iD = $category['CategoryID'];
                }
            }
        }
        return [$type, $iD];
    }

    public static $Types = ['d' => 'Discussion', 'c' => 'Comment', 'u' => 'User', 'cv' => 'Conversation', 'm' => 'Message'];

    public static function parseUID($uID) {
        // Strip off email stuff.
        if (preg_match('`<([^@]+)@`', $uID, $matches)) {
            $uID = trim(trim($matches[1]), '"');
        }

        if (strcasecmp($uID, 'noreply') == 0) {
            return ['noreply', null];
        }

        if (preg_match('`([a-z]+)-?([0-9]+)`i', $uID, $matches)) {
            $type = val($matches[1], self::$Types, null);
            if ($type) {
                $iD = $matches[2];
                return [$type, $iD];
            }
        } else {
            // This might be a category.
            $category = CategoryModel::categories($uID);
            if ($category) {
                return ['Category', $category['CategoryID']];
            } else {
                return [null, null];
            }
        }
    }

    protected function save($data, $sender) {
        $replyType = null;
        $replyID = null;

        if (val('ReplyTo', $data)) {
            trace("ReplyTo: {$data['ReplyTo']}");
            // See if we are replying to something specifically.
            list($replyType, $replyID) = self::parseUID($data['ReplyTo']);
        }

        if (!$replyType) {
            // Grab the reply from the to.
            list($toName, $toEmail) = self::parseEmailAddress(val('To', $data));
            list($replyType, $replyID) = self::parseType($toEmail);
        }

        if ((!$replyType || $replyType == 'Category') && val('ReplyTo', $data)) {
            // This may be replying to the SourceID rather than the UID.
            $saveType = $this->saveTypeFromRepyTo($data);
        }

        trace("Reply type: $replyType, Reply id: $replyID");

        if (strcasecmp($replyType, 'noreply') == 0) {
            return true;
        }

        // Save the full post for debugging.
        $data['Attributes'] = dbencode(arrayTranslate($data, ['Headers', 'Source']));

        $data['Body'] = self::stripEmail($data['Body']);
        if (!$data['Body']) {
            $data['Body'] = t('(empty message)');
        }

        list($fromName, $fromEmail) = self::parseEmailAddress($data['From']);

        // Check for a category.
        if ($replyType == 'Category') {
            $categoryID = $replyID;
        } else {
            $categoryID = c('Plugins.VanillaPop.DefaultCategoryID', -1);
        }
        if (!$categoryID) {
            $categoryID = -1;
        }
        touchValue('CategoryID', $data, $categoryID);

        // See if there is a user at the given email.
        $userModel = new UserModel();
        $user = $userModel->getByEmail($fromEmail);
        if (!$user) {
            if (c('Plugins.VanillaPop.AllowUserRegistration')) {
                saveToConfig('Garden.Registration.NameUnique', false, false);
                $sender->Data['_Status'][] = 'Creating user.';
                $user = [
                    'Name' => $fromName,
                    'Email' => $fromEmail,
                    'Password' => randomString(10),
                    'HashMethod' => 'Random',
                    'Source' => 'Email',
                    'SourceID' => $fromEmail
                ];

                $userID = $userModel->insertForBasic($user, false, ['NoConfirmEmail' => 'NoConfirmEmail']);

                if (!$userID) {
                    throw new Exception(t('Error creating user.').' '.$userModel->Validation->resultsText(), 400);
                }

                $user['UserID'] = $userID;
            } else {
                $this->sendEmail($fromEmail, '',
                    t("Whoops! You'll need to register before you can email our site."), $data);
                return true;
            }
        } else {
            $sender->Data['_Status'][] = 'User exists';
            $user = (array)$user;
        }
        Gdn::session()->start($user['UserID'], false);
        $data['InsertUserID'] = $user['UserID'];

        // Get the parent record and make sure the post is going in the right place.
        if (!isset($saveType)) {
            switch ($replyType) {
                case 'Discussion':
                    // Grab the discussion.
                    $discussionModel = new DiscussionModel();
                    $discussion = $discussionModel->getID($replyID);
                    if (!$discussion) {
                        $invalidReply = true;
                        $saveType = 'Discussion';
                    } else {
                        $saveType = 'Comment';
                        $data['DiscussionID'] = $replyID;
                        $data['CategoryID'] = val('CategoryID', $discussion);
                    }

                    break;
                case 'Comment':
                    $commentModel = new CommentModel();
                    $comment = $commentModel->getID($replyID, DATASET_TYPE_ARRAY);
                    if (!$comment) {
                        $invalidReply = true;
                        $saveType = 'Discussion';
                    } else {
                        // Grab the discussion so we can see its category.
                        $discussionModel = new DiscussionModel();
                        $discussion = $discussionModel->getID($comment['DiscussionID'], DATASET_TYPE_ARRAY);
                        $data['CategoryID'] = val('CategoryID', $discussion);

                        $saveType = 'Comment';
                        $data['DiscussionID'] = $comment['DiscussionID'];
                    }
                    break;
                case 'Conversation':
                    $conversationModel = new ConversationModel();
                    $conversation = $conversationModel->getID($replyID);
                    if (!$conversation) {
                        $invalidReply = true;
                        $saveType = 'Discussion';
                    } else {
                        // TODO: Check permission.

                        $saveType = 'Message';
                        $data['ConversationID'] = $conversation['ConversationID'];
                    }

                    break;
                case 'Message':
                    $messageModel = new ConversationMessageModel();
                    $message = $messageModel->getID($replyID, DATASET_TYPE_ARRAY);
                    if (!$message) {
                        $invalidReply = true;
                        $saveType = 'Discussion';
                    } else {
                        // TODO: Check permission.
                        $saveType = 'Message';
                        $data['ConversationID'] = $message['ConversationID'];
                    }
                    break;
                default:
                    $saveType = 'Discussion';
                    break;
            }
        }

        if (isset($invalidReply)) {
            $data['Body'] .= "\n\n".sprintf(t('Note: The email was trying to reply to an invalid %s.'), "$replyType ($replyID)");
        }

        // Set the source of the post.
        $data['Source'] = 'Email';
        $data['SourceID'] = val('MessageID', $data, null);
        unset($data['MessageID']);

        $category = CategoryModel::categories(val('CategoryID', $data));
        if ($category) {
            $permissionCategoryID = $category['PermissionCategoryID'];
        } else {
            $permissionCategoryID = -1;
        }

        trace("Save type: {$saveType}, Permission Category ID: {$permissionCategoryID}");

        switch ($saveType) {
            case 'Comment':
                if (!Gdn::session()->checkPermission('Email.Comments.Add')) {
                    trace("Doesn't have Email.Comments.Add");

                    $this->sendEmail($fromEmail, '',
                        t("Sorry! You don't have permission to comment through email."), $data);
                    return true;
                } elseif (!Gdn::session()->checkPermission('Vanilla.Comments.Add', true, 'Category', $permissionCategoryID)) {
                    trace("Doesn't have Vanilla.Comments.Add for category", TRACE_WARNING);

                    $this->sendEmail($fromEmail, '',
                        t("Sorry! You don't have permission to post right now."), $data);
                    return true;
                } elseif (val('Closed', $discussion)) {
                    $this->sendEmail($fromEmail, '',
                        t("Sorry! This discussion has been closed."), $data);
                    return true;
                }

                $commentModel = new CommentModel();

                // Make sure there isn't already a comment saved from this email.
                if ($data['SourceID']) {
                    $existingComment = $commentModel->getWhere(['Source' => 'Email', 'SourceID' => $data['SourceID']])->firstRow();
                    if ($existingComment) {
                        trace("This email has already been saved.");
                        return true;
                    }
                }

                $commentID = $commentModel->save($data);
                if (!$commentID) {
                    throw new Exception($commentModel->Validation->resultsText().print_r($data, true), 400);
                } else {
                    $commentModel->save2($commentID, true);
                }
                trace("Saved comment $commentID");
                return $commentID;
            case 'Message':
                if (!Gdn::session()->checkPermission('Email.Conversations.Add')) {
                    $this->sendEmail($fromEmail, '',
                        t("Sorry! You don't have permission to send messages through email."), $data);
                    return true;
                }

                $messageModel = new ConversationMessageModel();
                $messageID = $messageModel->save($data);
                if (!$messageID) {
                    throw new Exception($messageModel->Validation->resultsText().print_r($data, true), 400);
                }
                return $messageID;
            case 'Discussion':
            default:
                // Check the permission on the discussion.
                if (!Gdn::session()->checkPermission('Email.Discussions.Add')) {
                    trace("Doesn't have Email.Discussions.Add");

                    $this->sendEmail($fromEmail, '',
                        t("Sorry! You don't have permission to post discussions/questions through email."), $data);
                    return true;
                } elseif (!Gdn::session()->checkPermission('Vanilla.Discussions.Add', true, 'Category', $permissionCategoryID)) {
                    trace("Sorry! You don't have permission to post right now.", TRACE_WARNING);

                    $this->sendEmail($fromEmail, '',
                        t("Sorry! You don't have permission to post right now."), $data);
                    return true;
                }

                $data['Name'] = $data['Subject'];
                $data['UpdateUserID'] = $data['InsertUserID'];
                $discussionModel = new DiscussionModel();
                $discussionID = $discussionModel->save($data);
                if (!$discussionID) {
                    throw new Exception($discussionModel->Validation->resultsText().print_r($data, true), 400);
                }
                trace("Saved discussion $discussionID");

                // Send a confirmation email.
                if (c('Plugins.VanillaPop.SendConfirmationEmail')) {
                    $data['DiscussionID'] = $discussionID;
                    $this->sendConfirmationEmail($data, $user);
                }

                return $discussionID;
        }
    }

    public function saveTypeFromRepyTo(&$data) {
        $tables = [
            'Discussion' => ['Comment', 'DiscussionID'],
            'Comment' => ['Comment', 'DiscussionID'],
            'ConversationMessage' => ['Message', 'ConversationID']];

        $replyTo = trim(val('ReplyTo', $data));
        if (!$replyTo) {
            return null;
        }

        foreach ($tables as $name => $info) {
            $row = Gdn::sql()->getWhere($name, ['Source' => 'Email', 'SourceID' => $replyTo])->firstRow(DATASET_TYPE_ARRAY);
            if ($row) {
                $result = $info[0];
                $data[$info[1]] = $row[$info[1]];
                $data['ParentID'] = $row[$info[1]];
                return $result;
            }
        }
        return null;
    }

    public function sendEmail($to, $subject, $body, $quote = false) {
        trace("Email: $body");

        $email = new Gdn_Email();
        $email->to($to);
        $email->subject(sprintf('[%s] %s', c('Garden.Title'), $subject));
        $from = $email->PhpMailer->From;
        $email->PhpMailer->From = self::addIDToEmail($from, 'noreply');

        if (is_array($quote)) {
            $messageID = val('MessageID', $quote);
            if ($messageID) {
                $email->PhpMailer->addCustomHeader("In-Reply-To:$messageID");
                $email->PhpMailer->addCustomHeader("References:$messageID");
            }

            $subject = val('Subject', $quote);
            if ($subject) {
                $email->subject(sprintf('Re: [%s] %s', c('Garden.Title'), ltrim(stringBeginsWith($subject, 'Re:', true, true))));
            }
        }

        $message = self::formatEmailBody($body, false, $quote);

        $email->message($message);
        @$email->send();
    }

    /**
     * Set the from address to the name of the user that sent the notification.
     *
     * @param Gdn_Email $PhpMailer
     * @param int|array
     */
    public function setFrom($email, $user) {
        if (!c('Plugins.VanillaPop.OverrideFrom', true)) {
            return;
        }

        if (is_numeric($user)) {
            $user = Gdn::userModel()->getID($user);
        }

        $email->PhpMailer->FromName = val('Name', $user);
    }

    /**
     * Send the initial confirmation email when a discussion is first started through email.
     *
     * @param type $discussion
     * @param type $user
     */
    public function sendConfirmationEmail($discussion, $user) {
        $formatData = $discussion;
        $formatData['Title'] = c('Garden.Title');
        $formatData['ID'] = $discussion['DiscussionID'];
        $formatData['Category'] = CategoryModel::categories($discussion['CategoryID']);
        $formatData['Url'] = externalUrl('/discussion/'.$discussion['DiscussionID'].'/'.Gdn_Format::url($discussion['Name']));

        $formatData['Quote'] = self::formatQuoteText($formatData['Body']);

        $canView = Gdn::userModel()->getCategoryViewPermission($user['UserID'], val('CategoryID', $discussion));
        $canReply = self::checkUserPermission($user['UserID'], 'Email.Comments.Add');
        $route = '/discussion/'.$discussion['DiscussionID'].'/'.Gdn_Format::url($discussion['Name']);
        $formatData['Signature'] = self::emailSignature($route, $canView, $canReply);

        $email = new Gdn_Email();

        $message = formatString(c('EmailFormat.ConfirmationBody', self::$FormatDefaults['ConfirmationBody']), $formatData);
        $email->message($message);

        // We are using the standard confirmation subject because some email clients won't group emails unless their subject are the exact same.
        $subject = formatString(c('EmailFormat.DiscussionSubject', self::$FormatDefaults['DiscussionSubject']), $formatData);
        $email->subject($subject);

        $email->PhpMailer->MessageID = self::uid('Discussion', $discussion['DiscussionID'], 'email');
        $email->PhpMailer->From = self::addIDToEmail($email->PhpMailer->From, self::uid('Discussion', $discussion['DiscussionID']));
        $email->to($user['Email'], $user['Name']);

        $replyTo = val('SourceID', $discussion);
        if (isset($replyTo)) {
            $email->PhpMailer->addCustomHeader("In-Reply-To:$replyTo");
            $email->PhpMailer->addCustomHeader("References:$replyTo");
        }


        try {
            $email->send();
        } catch (Exception $ex) {
            // Do nothing for now...
            if (debug()) {
                throw $ex;
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

    public static function simpleForm($form, $schema) {
        echo '<ul>';
        foreach ($schema as $index => $row) {
            if (is_string($row)) {
                $row = ['Name' => $index, 'Control' => $row];
            }

            if (!isset($row['Name'])) {
                $row['Name'] = $index;
            }
            if (!isset($row['Options'])) {
                $row['Options'] = [];
            }

            echo "<li>\n  ";

            $labelCode = self::labelCode($row);

            $description = val('Description', $row, '');
            if ($description) {
                $description = '<div class="Info">'.$description.'</div>';
            }

            touchValue('Control', $row, 'TextBox');

            switch (strtolower($row['Control'])) {
                case 'checkbox':
                    echo $description;
                    echo $form->checkBox($row['Name'], t($labelCode));
                    break;
                case 'dropdown':
                    echo $form->label($labelCode, $row['Name']);
                    echo $description;
                    echo $form->dropDown($row['Name'], $row['Items'], $row['Options']);
                    break;
                case 'radiolist':
                    echo $description;
                    echo $form->radioList($row['Name'], $row['Items'], $row['Options']);
                    break;
                case 'checkboxlist':
                    echo $form->label($labelCode, $row['Name']);
                    echo $description;
                    echo $form->checkBoxList($row['Name'], $row['Items'], null, $row['Options']);
                    break;
                case 'textbox':
                    echo $form->label($labelCode, $row['Name']);
                    echo $description;
                    echo $form->textBox($row['Name'], $row['Options']);
                    break;
                default:
                    echo "Error a control type of {$row['Control']} is not supported.";
                    break;
            }
            echo "\n</li>\n";
        }
        echo '</ul>';
    }

    public static function stripSignature($body) {
        $i = strrpos($body, "\n--");
        if ($i === false) {
            return $body;
        }
        $j = strpos($body, "\n", $i + 1);
        if ($j === false) {
            return $body;
        }

        $delim = trim(substr($body, $i, $j - $i + 1));
        if (preg_match('`^-+$`', $delim)) {
            $body = trim(substr($body, 0, $i));
        }

        return $body;
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

    public static function stripEmail($body) {
        $sigFound = false;
        $inQuotes = 0;

        $body = str_replace("\r\n", "\n", $body);

        $lines = explode("\n", trim($body));
        $lastLine = count($lines);

        for ($i = $lastLine - 1; $i >= 0; $i--) {
            $line = $lines[$i];

            if ($inQuotes === 0 && preg_match('`^\s*[>|]`', $line)) {
                // This is a quote line.
                $lastLine = $i;
            } elseif (!$sigFound && preg_match('`^\s*--`', $line)) {
                // -- Signature delimiter.
                $lastLine = $i;
                $sigFound = true;
            } elseif (preg_match('`^\s*---.+---\s*$`', $line)) {
                // This will catch an ------Original Message------ heade
                $lastLine = $i;
                $inQuotes = false;
            } elseif ($inQuotes === 0) {
                if (preg_match('`wrote:\s*$`i', $line)) {
                    // This is the quote line...
                    $lastLine = $i;
                    $inQuotes = false;

                    $prevLine = val($i - 1, $lines);
                    $onRegex = '`^On\s+`i';
                    if (!preg_match($onRegex, $line) && preg_match($onRegex, $prevLine)) {
                        $i--;
                        $lastLine = $i;
                    }
                } elseif (preg_match('`^\s*$`', $line)) {
                    $lastLine = $i;
                } else {
                    $inQuotes = false;
                }
            }
        }

        if ($lastLine >= 1) {
            $lines = array_slice($lines, 0, $lastLine);
        }
        $result = trim(implode("\n", $lines));
        return $result;
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
     * @param ActivityModel $sender
     * @param array $args
     */
    public function activityModel_beforeSendNotification_handler($sender, $args) {
        if (isset($args['RecordType']) && isset($args['RecordID'])) {
            $type = $args['RecordType'];
            $iD = $args['RecordID'];
        } else {
            list($type, $iD) = self::parseRoute(val('Route', $args));
        }

        $userAuthorized = $args["UserAuthorized"] ?? false;
        $formatData = ['Title' => c('Garden.Title'), 'Signature' => self::emailSignature(val('Route', $args))];
        $notifyUserID = getValueR('Activity.NotifyUserID', $args);

        if (in_array($type, ['Discussion', 'Comment', 'Conversation', 'Message'])) {
            $email = $args['Email']; //new gdn_Email(); //
            $story = val('Story', $args);

            switch ($type) {
                case 'Discussion':
                    $discussionModel = new DiscussionModel();
                    $discussion = $discussionModel->getID($iD);
                    if ($discussion) {
                        if (!$userAuthorized) {
                            // See if the user has permission to view this discussion on the site.
                            $canView = Gdn::userModel()->getCategoryViewPermission($notifyUserID, val('CategoryID', $discussion));
                            $canReply = self::checkUserPermission($notifyUserID, 'Email.Comments.Add');
                        } else {
                            // If there isn't one specific user being notified, avoid attempting permission checks.
                            $canView = $canReply = true;
                        }
                        $formatData['Signature'] = self::emailSignature(val('Route', $args), $canView, $canReply);

                        $discussion = (array)$discussion;
                        $discussion['Name'] = Gdn_Format::plainText($discussion['Name'], 'Text');

                        $body = Gdn_Format::to($discussion['Body'], $discussion['Format']);
                        $body = $this->transformQuotes($body);
                        $body = Gdn_Format::plainText($body, 'html', true);
                        $body = $this->parseQuotes($body);

                        $discussion['Body'] = $body;
                        $discussion['Category'] = CategoryModel::categories($discussion['CategoryID']);
                        $discussion['Url'] = externalUrl('/discussion/'.$discussion['DiscussionID'].'/'.Gdn_Format::url($discussion['Name']));
                        $formatData = array_merge($formatData, $discussion);

                        $message = formatString(c('EmailFormat.DiscussionBody', self::$FormatDefaults['DiscussionBody']), $formatData);
                        $email->message($message);

                        $subject = formatString(c('EmailFormat.DiscussionSubject', self::$FormatDefaults['DiscussionSubject']), $formatData);
                        $email->subject($subject);

                        $this->setFrom($email, $discussion['InsertUserID']);
                        $email->PhpMailer->From = self::addIDToEmail($email->PhpMailer->From, self::uid('Discussion', val('DiscussionID', $discussion)));
                    }
                    break;
                case 'Comment':
                    $commentModel = new CommentModel();
                    $comment = $commentModel->getID($iD, DATASET_TYPE_ARRAY);

                    if ($comment) {
                        $body = Gdn_Format::to($comment['Body'], $comment['Format']);
                        $body = $this->transformQuotes($body);
                        $body = Gdn_Format::plainText($body, 'html', true);
                        $body = $this->parseQuotes($body);

                        $comment['Body'] = $body;
                        $comment['Url'] = externalUrl(val('Route', $args));
                        $comment = [$comment];
                        Gdn::userModel()->joinUsers($comment, ['InsertUserID', 'UpdateUserID']);
                        $comment = $comment[0];

                        if (in_array(getValueR('Activity.ActivityType', $args), ['AnswerAccepted'])) {
                            $comment['Body'] = Gdn_Format::plainText($args['Headline'], 'Html')."\n\n".$comment['Body'];
                        }

                        $formatData = array_merge($formatData, $comment);

                        $this->setFrom($email, $comment['InsertUserID']);

                        $discussionModel = new DiscussionModel();
                        $discussion = (array)$discussionModel->getID($comment['DiscussionID']);

                        if ($discussion) {
                            if (!$userAuthorized) {
                                // See if the user has permission to view this discussion on the site.
                                $canView = Gdn::userModel()->getCategoryViewPermission($notifyUserID, val('CategoryID', $discussion));
                                $canReply = self::checkUserPermission($notifyUserID, 'Email.Comments.Add');
                            } else {
                                // If there isn't one specific user being notified, avoid attempting permission checks.
                                $canView = $canReply = true;
                            }
                            $formatData['Signature'] = self::emailSignature(val('Route', $args), $canView, $canReply); //.print_r(array('CanView' => $CanView, 'CanReply' => $CanReply), true);

                            $discussion['Name'] = Gdn_Format::plainText($discussion['Name'], 'Text');
                            $discussion['Body'] = Gdn_Format::plainText($discussion['Body'], $discussion['Format']);
                            $discussion['Url'] = externalUrl('/discussion/'.$discussion['DiscussionID'].'/'.Gdn_Format::url($discussion['Name']));
                            $formatData['Discussion'] = $discussion;
                            $formatData['Category'] = CategoryModel::categories($discussion['CategoryID']);

                            $message = formatString(c('EmailFormat.CommentBody', self::$FormatDefaults['CommentBody']), $formatData);
                            $email->message($message);

                            $subject = formatString(c('EmailFormat.CommentSubject', self::$FormatDefaults['CommentSubject']), $formatData);
                            $email->subject($subject);

                            $source = val('Source', $discussion);
                            if ($source == 'Email') {
                                 // replying to an email...
                                $replyTo = val('SourceID', $discussion);
                            }
                            else {
                                $replyTo = self::uid('Discussion', val('DiscussionID', $discussion), 'email');
                            }

                            $email->PhpMailer->From = self::addIDToEmail($email->PhpMailer->From, self::uid('Discussion', val('DiscussionID', $discussion)));
                        }
                    }

                    break;
                case 'Message':
                    // Get this message.
                    $message = Gdn::sql()->getWhere('ConversationMessage', ['MessageID' => $iD])->firstRow(DATASET_TYPE_ARRAY);
                    if ($message) {
                        $conversationID = $message['ConversationID'];
                        $this->setFrom($email, $message['InsertUserID']);

                        // Get the message before this one.
                        $message2 = Gdn::sql()
                            ->select('*')
                            ->from('ConversationMessage')
                            ->where('ConversationID', $conversationID)
                            ->where('MessageID <', $iD)
                            ->orderBy('MessageID', 'desc')
                            ->limit(1)
                            ->get()->firstRow(DATASET_TYPE_ARRAY);

                        if ($message2) {
                            if ($message2['Source'] == 'Email') {
                                $replyTo = $message2['SourceID'];
                            } else {
                                $replyTo = self::uid('Message', $message2['MessageID'], 'email');
                            }
                        }

                        $email->PhpMailer->From = self::addIDToEmail($email->PhpMailer->From, self::uid('Message', val('MessageID', $message)));
                    }

                    // See if the user has permission to view this discussion on the site.
                    $canView = true;
                    $canReply = self::checkUserPermission($notifyUserID, 'Email.Conversations.Add');
                    $formatData['Signature'] = self::emailSignature(val('Route', $args), $canView, $canReply);

                    $message = Gdn_Format::to($message['Body'], $message['Format']);
                    $message = $this->transformQuotes($message);
                    $message = Gdn_Format::plainText($message, 'html', true);
                    $message = $this->parseQuotes($message);
                    $message .= "\n\n-- \n".$formatData['Signature'];
                    $email->message($message);

                    break;
            }
            if (isset($replyTo)) {
                $email->PhpMailer->addCustomHeader("In-Reply-To:$replyTo");
                $email->PhpMailer->addCustomHeader("References:$replyTo");
            }
            $email->PhpMailer->MessageID = self::uid($type, $iD, 'email');
        }
    }

    /**
     * Add notifications.
     */
    public function commentModel_beforeNotification_handler($sender, $args) {
        // Make sure the discussion's user is notified if they started the discussion by email.
        if (getValueR('Discussion.Source', $args) == 'Email') {
            $notifiedUsers = (array)val('NotifiedUsers', $args);
            $insertUserID = getValueR('Discussion.InsertUserID', $args);

            // Construct an activity and send it.
            $activityModel = $args['ActivityModel'];

            $comment = $args['Comment'];
            $commentID = $comment['CommentID'];
            $headlineFormat = t('HeadlineFormat.Comment', '{ActivityUserID,user} commented on <a href="{Url,html}">{Data.Name,text}</a>');

            $activity = [
                'ActivityType' => 'Comment',
                'ActivityUserID' => $comment['InsertUserID'],
                'NotifyUserID' => $insertUserID,
                'HeadlineFormat' => $headlineFormat,
                'RecordType' => 'Comment',
                'RecordID' => $commentID,
                'Route' => "/discussion/comment/$commentID#Comment_$commentID",
                'Data' => ['Name' => val('Name', $args['Discussion'])],
                'Notified' => ActivityModel::SENT_OK,
                'Emailed' => ActivityModel::SENT_PENDING
            ];

            $activityModel->queue($activity, false, ['Force' => true]);
        }

        // Notify anyone in a ForceNotify role
        $this->forceNotify($sender, $args);
    }

    /**
     * Add notifications.
     */
    public function discussionModel_beforeNotification_handler($sender, $args) {
        // Notify anyone in a ForceNotify role
        $this->forceNotify($sender, $args);
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

    public function gdn_Dispatcher_BeforeBlockDetect_Handler($sender, $args) {
        $args['BlockExceptions']['`post/sendgrid(\/.*)?$`'] = Gdn_Dispatcher::BLOCK_NEVER;
    }

    public function utilityController_email_create($sender, $args = []) {
        if (Gdn::session()->UserID == 0) {
            Gdn::session()->start(Gdn::userModel()->getSystemUserID(), false);
            Gdn::session()->User->Admin = false;
        }

        if ($sender->Form->isPostBack()) {
            $data = $sender->Form->formValues();
            trace('Saving data.');
            if ($this->save($data, $sender)) {
                $sender->StatusMessage = t('Saved');
                $sender->setData('Saved', true);
                $sender->setData('Trace', trace());
            }
        }

        $sender->setData('Title', t('Post an Email'));
        $sender->render('Email', '', 'plugins/VanillaPop');
    }

    public function postController_sendgrid_create($sender, $args = []) {
        $this->utilityController_sendgrid_create($sender, $args);
    }

    /**
     *
     * @param PostController $sender
     * @param array $args
     */
    public function utilityController_sendgrid_create($sender, $args = []) {
        try {
            Gdn::session()->start(Gdn::userModel()->getSystemUserID(), false);
            Gdn::session()->User->Admin = false;

            if ($sender->Form->isPostBack()) {
                self::log("Postback");

                self::log("Getting post...");
                $post = $sender->Form->formValues();
                self::log("Post got...");
                $data = arrayTranslate($post, [
                    'from' => 'From',
                    'to' => 'To',
                    'subject' => 'Subject'
                ]);

                //         self::log('Parsing headers.'.val('headers', $Post, ''));
                $headers = self::parseEmailHeader(val('headers', $post, ''));
                //         self::log('Headers: '.print_r($Headers, true));
                $headers = array_change_key_case($headers);
                $headerData = arrayTranslate($headers, ['message-id' => 'MessageID', 'references' => 'References', 'in-reply-to' => 'ReplyTo']);
                $data = array_merge($data, $headerData);

                if (false && val('html', $post)) {
                    $data['Body'] = $post['html'];
                    $data['Format'] = 'Html';
                } else {
                    $data['Body'] = $post['text'];
                    $data['Format'] = 'Html';
                }

                self::log("Saving data...");
                $sender->Data['_Status'][] = 'Saving data.';


                if ($this->save($data, $sender)) {
                    $sender->StatusMessage = t('Saved');
                } else {
                    throw new Exception('Could not save...', 400);
                }
            }

            $sender->setData('Title', t('Sendgrid Proxy'));
            $sender->render('Sendgrid', '', 'plugins/VanillaPop');
        } catch (Exception $ex) {
            $contents = $ex->getMessage()."\n"
                .$ex->getTraceAsString()."\n"
                .print_r($_POST, true);
            file_put_contents(PATH_UPLOADS.'/email/error_'.time().'.txt', $contents);

            throw $ex;
        }
    }

    /**
     *
     * @param SettingsController $sender
     * @param array $args
     */
    public function settingsController_vanillaPop_create($sender, $args = []) {
        $sender->permission('Garden.Settings.Manage');

        $confSettings = [
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

        foreach (self::$FormatDefaults as $name => $default) {
            $options = val('Options', $confSettings['EmailFormat.'.$name], []);
            if (stringEndsWith($name, 'Body')) {
                $options['Multiline'] = true;
            }
            $confSettings['EmailFormat.'.$name] = ['Control' => 'TextBox', 'Default' => $default, 'Options' => $options];
        }

        $conf = new ConfigurationModule($sender);
        $conf->initialize($confSettings);

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
        $sender->ConfigurationModule = $conf;
//      $Conf->renderAll();
        $sender->render('Settings', '', 'plugins/VanillaPop');
    }

    /**
     * Allow roles to be configured to force email notifications.
     */
    public function base_beforeRolePermissions_handler($sender) {
        if (!c('Plugins.VanillaPop.AllowForceNotify')) {
            return;
        }

        $notifyOptions = [
            0 => 'Notify these users normally using their preferences (recommended)',
            1 => 'Notify these users for every new comment and discussion',
            2 => 'Notify these users for new announcements'
        ];

        $sender->Data['_ExtendedFields']['ForceNotify'] = [
            'LabelCode' => 'Notifications Override',
            'Control' => 'DropDown',
            'Items' => $notifyOptions
        ];

    }

    /**
     * Send forced email notifications.
     *
     * @param Gdn_Controller $sender Sending controller.
     * @param array $args Event's arguments.
     */
    public function forceNotify($sender, $args) {
        if (!c('Plugins.VanillaPop.AllowForceNotify')) {
            return;
        }

        $activity = $args['Activity'];
        $activityModel = $args['ActivityModel'];
        $activityType = (isset($args['Comment'])) ? 'Comment' : 'Discussion';

        // Email them.
        $activity['Emailed'] = ActivityModel::SENT_PENDING;

        // Get effected roles.
        $roleModel = new RoleModel();
        if ($activityType === 'Discussion' && val('Announce', $args['Discussion'])) {
            // Add everyone with force notify all OR announcement-only option.
            $wheres = ['ForceNotify >' => 0];
        } else {
            // Only get users with force notify all.
            $wheres = ['ForceNotify' => 1];
        }
        $roles = $roleModel->getWhere($wheres)->resultArray();

        // Filter roles by the category's permissions.
        $categoryID = valr('Discussion.CategoryID', $args, '-1');
        $category = CategoryModel::categories($categoryID);
        $categoryPermissionID = $category['PermissionCategoryID'];
        $categoryModel = new CategoryModel();
        $categoryPermissions = $categoryModel->getRolePermissions($categoryPermissionID);
        $categoryPermissions = Gdn_DataSet::index($categoryPermissions, 'RoleID');

        $roleIDs = [];
        foreach ($roles as $role) {
            $roleId = $role['RoleID'];

            // Check the category's permissions that is configured on the role. This checks has to be made first.
            $roleCategoryPermission = $roleModel->getCategoryPermissions($roleId);
            $roleCategoryPermission = Gdn_DataSet::index($roleCategoryPermission, 'CategoryID');
            if (isset($roleCategoryPermission[$categoryID])) {
                $viewPermission = val('Vanilla.Discussions.View', $roleCategoryPermission[$categoryID], null);
            }

            // Check the role's permissions that is configured on the effective category.
            // The effective category is the one that has the permissions. We fetched it using $category['PermissionCategoryID'].
            if ($viewPermission === null) {
                $categoryRolePermission = val($roleId, $categoryPermissions, []);
                $viewPermission = val('Vanilla.Discussions.View', $categoryRolePermission, 0);
            }

            if ($viewPermission) {
                $roleIDs[] = $roleId;
            }
        }

        if ($roleIDs) {
            // Get users in those roles.
            $userRoles = $sender->SQL
                ->select('UserID')
                ->distinct()
                ->from('UserRole')
                ->whereIn('RoleID', $roleIDs)
                ->get()->resultArray();


            // Add an activity for each person and pray we don't melt the wibbles.
            foreach ($userRoles as $userRole) {
                $activity['NotifyUserID'] = $userRole['UserID'];
                $activityModel->queue($activity, false, ['Force' => true]);
            }
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
