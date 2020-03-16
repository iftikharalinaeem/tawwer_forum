<?php
/**
 * @copyright 2011-2015 Vanilla Forums, Inc.
 * @package Badges
 */

// We can't rely on our autoloader in a plugin.
require_once(dirname(__FILE__).'/class.badgesappcontroller.php');

/**
 * Individual badges and doling to users.
 *
 * @since 1.0.0
 */
class BadgeController extends BadgesAppController {

    /**
     * Before any call to the controller.
     *
     * @since 1.0.0
     */
    public function initialize() {
        parent::initialize();
        $this->title('Badges');
    }

    /**
     * Manage badges.
     *
     * @since 1.0.0
     */
    public function all() {
        Gdn_Theme::section('Dashboard');
        $this->permission('Garden.Settings.Manage');
        $this->setData('Badges', $this->BadgeModel->getList());
        $this->render();
    }

    /**
     * Approve badge request.
     *
     * @since 1.1
     * @param int $userID
     * @param string|int $badgeID
     * @param string $transientKey
     */
    public function approve($userID = '', $badgeID = '', $transientKey = '') {
        $this->permission('Reputation.Badges.Give');

        if (Gdn::session()->validateTransientKey($transientKey)) {
            $this->UserBadgeModel->give($userID, $badgeID); // No reason for now
            $this->informMessage(t('Badge request approved.'));
        }

        if ($this->_DeliveryType == DELIVERY_TYPE_BOOL) {
            return $this->Form->errorCount() == 0 ? true : $this->Form->errors();
        } else {
            $this->requests();
        }
    }

    /**
     * Decline badge request.
     *
     * @since 1.1
     * @param int $userID
     * @param string|int $badgeID
     * @param string $transientKey
     */
    public function decline($userID = '', $badgeID = '', $transientKey = '') {
        $this->permission('Reputation.Badges.Give');

        if (Gdn::session()->validateTransientKey($transientKey)) {
            $this->UserBadgeModel->declineRequest($userID, $badgeID);
            $this->informMessage(t('Badge request declined.'));
        }

        if ($this->_DeliveryType == DELIVERY_TYPE_BOOL) {
            return $this->Form->errorCount() == 0 ? true : $this->Form->errors();
        } else {
            $this->requests();
        }
    }

    /**
     * Delete an badge & revoke from all users.
     *
     * @since 1.0.0
     * param string|int $badgeID
     */
    public function delete($badgeID = '') {
        $this->permission('Garden.Settings.Manage');

        // Validate BadgeID
        if (!is_numeric($badgeID)) {
            redirectTo('/badge/all');
        }

        $badge = $this->BadgeModel->getID($badgeID);
        if (!$badge) {
            throw notFoundException('badge');
        }

        // Form setup
        $this->Form->setModel($this->BadgeModel);

        // Form submitted (confirmation)
        if ($this->Form->authenticatedPostBack()) {
            $badge = $this->BadgeModel->getID($badgeID);
            if (val('CanDelete', $badge, false)) {
                // Delete & revoke
                $this->BadgeModel->delete(['BadgeID' => $badgeID]);
                $this->UserBadgeModel->delete(['BadgeID' => $badgeID]);

                // Success & redirect
                $this->informMessage(t('Badge deleted.'));
                $this->setRedirectTo('/badge/all');
            } else {
                // Failure & redirect
                $this->informMessage(t('Badge cannot be deleted.'));
                $this->setRedirectTo('/badge/all');
            }
        } else {
            // Get info for confirmation
            $this->Badge = $this->BadgeModel->getID($badgeID);
            if (!$this->Badge) {
                throw new Exception(t('Badge404', 'Badge not found.'), 404);
            }
        }

        $this->render('blank', 'utility', 'dashboard');
    }

    /**
     * Disable/enable an badge from being given. It will still show on users who have it.
     *
     * @since 1.0.0
     * @param string|int $badgeID
     * @oaram string $transientKey
     */
    public function disable($badgeID = '', $transientKey = '') {
        $this->permission('Garden.Settings.Manage');
        $session = Gdn::session();

        if ($session->validateTransientKey($transientKey) && is_numeric($badgeID)) {
            // Reverse whether it's active
            $value = (val('Active', $this->BadgeModel->getID($badgeID))) ? 0 : 1;
            $this->BadgeModel->setProperty($badgeID, 'Active', $value);
            $message = ($value) ? 'Badge disabled.' : 'Badge enabled.';
            $this->informMessage($message);
        }

        if ($this->deliveryType() === DELIVERY_TYPE_ALL) {
            redirectTo('badge/all');
        }

        // Regenerate the view we take this action from.
        $this->View = 'all';
        $this->render();
    }

    /**
     * Give selected badge to 1 or more users.
     *
     * @since 1.0.0
     * @param string|int $badgeID
     */
    public function give($badgeID = '') {
        $this->permission('Reputation.Badges.Give');

        // Validate BadgeID
        if (!is_numeric($badgeID)) {
            redirectTo('/badge/all');
        }

        // Get info & confirm enabled
        $badge = $this->BadgeModel->getID($badgeID);
        $this->setData('Badge', $badge);
        if (!$badge['Active']) {
            $this->Form->addError('Badge is not available.');
        }

        // Form setup
        $this->Form->setModel($this->UserBadgeModel);

        // Form submitted
        if ($this->Form->authenticatedPostBack()) {
            // Set BadgeID
            $this->Form->setFormValue('BadgeID', $badgeID);

            // Get reason
            $reason = $this->Form->getFormValue('Reason');

            // Set recipients
            $to = explode(',', $this->Form->getFormValue('To', ''));
            $userModel = new UserModel();
            $result = true;
            foreach ($to as $name) {
                if (trim($name) != '') {
                    $user = $userModel->getByUsername(trim($name));
                    if (is_object($user)) {
                        $userBadge = $this->UserBadgeModel->getID($user->UserID, val('BadgeID', $badge));
                        if (val('DateCompleted', $userBadge, null) !== null) {
                            // User already has this badge.
                            continue;
                        }

                        $saved = $this->UserBadgeModel->give($user->UserID, $badgeID, $reason);
                        $result = $result && $saved;
                    }
                }
            }

            if ($result) {
                $this->informMessage(t('Gave badge to users.'));
                $this->setRedirectTo('/badge/all');
            } else {
                $this->Form->addError(t('Failed to give badge to users.'));
            }
        }

        $this->render();
    }

    /**
     * Give any badge to selected user.
     *
     * @since 1.0.0
     * @param int $userID
     */
    public function giveUser($userID = '') {
        $this->permission('Reputation.Badges.Give');

        // Validate $UserID
        if (empty($userID)) {
            $postUserID = Gdn::request()->post('UserID', false);
            if (is_null($postUserID)) {
                throw notFoundException('UserID');
            }

            $userID = $postUserID;
        }

        // Get user data
        $userModel = new UserModel();
        $this->setData('User', $userModel->getID($userID), true);
        if (!$this->data('User')) {
            throw notFoundException('User');
        }

        // Form setup
        $this->Form->setModel($this->UserBadgeModel);

        // Form submitted
        if ($this->Form->authenticatedPostBack()) {
            // Validate badge by getting its data
            $badgeID = $this->Form->getFormValue('BadgeID');
            $badge = $this->BadgeModel->getID($badgeID);
            $reason = $this->Form->getFormValue('Reason');

            if ($badge) {
                // Give Badge
                $saved = $this->UserBadgeModel->give($this->User->UserID, $badgeID, $reason);
                $this->setData('Awarded', $saved);

                $this->Form->setValidationResults($this->UserBadgeModel->Validation->results());
                $this->UserBadgeModel->Validation->results(true);

                // Continue
                if ($saved) {
                    $userBadge = $this->UserBadgeModel->getByUser($userID, $badgeID);
                    $outputBadge = array_merge((array)$badge, (array)$userBadge);
                    $this->setData('Badge', $outputBadge);

                    $this->informMessage(t('Gave badge to user.'));
                    $this->setRedirectTo('profile/'.$userID.'/'.val('Name', $this->User));
                }
            } else {
                throw notFoundException('Badge');
            }
        }

        // Get badge list for dropdown
        $this->BadgeData = $this->BadgeModel->getMenu();
        $this->render();
    }

    /**
     * Hide/Unhide an badge from being listed. It will still show on users who have it.
     *
     * @since 1.0.0
     * @param string|int $badgeID
     * @param string $transientKey
     */
    public function hide($badgeID = '', $transientKey = '') {
        $this->permission('Garden.Settings.Manage');
        $session = Gdn::session();

        if ($session->validateTransientKey($transientKey) && is_numeric($badgeID)) {
            // Reverse visibility
            $value = ($this->BadgeModel->getID($badgeID)->Visible) ? 0 : 1;
            $this->BadgeModel->setProperty($badgeID, 'Visible', $value);
            $message = ($value) ? 'Badge unhidden.' : 'Badge hidden.';
            $this->informMessage($message);
        }

        if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
            redirectTo(getIncomingValue('Target', $this->SelfUrl));
        }

        $this->setView404();
        $this->render();
    }

    /**
     * View a badge.
     *
     * @since 1.0.0
     * @param string|int $badgeID
     * @param string $name
     */
    public function index($badgeID = '', $name = '') {
        $this->permission('Reputation.Badges.View');
        $this->MasterView = 'default';
        $this->addCssFile('style.css');
        $this->removeCssFile('admin.css');
        Gdn_Theme::section('Badge');

        // Get badge data or 404
        $this->Badge = $this->BadgeModel->getID($badgeID);
        if (!$this->Badge) {
            throw new Exception(t('Badge404', 'Badge not found.'), 404);
        }

        // Don't show badge descriptions for the non-default locale since they can't be translated.
        if (Gdn::locale()->current() !== c('Garden.Locale')) {
            $this->Badge['Body'] = '';
        }

        $this->setData('Badge', $this->Badge);

        // Current user a recipient?
        $this->UserBadge = false;
        if (Gdn::session()->isValid()) {
            $this->UserBadge = $this->UserBadgeModel->getByUser(Gdn::session()->User->UserID, $this->data('Badge.BadgeID'));
        }
        $this->setData('UserBadge', $this->UserBadge);

        // Get recipients
        $this->setData('Recipients', $this->UserBadgeModel->getUsers($badgeID, ['Limit' => 15])->resultArray());
        $this->setData('BadgeID', $badgeID, true);

        if (val('_New', $this->UserBadge) && BadgeModel::isRequestable($this->Badge)) {
            $this->addModule('RequestBadgeModule');
        }
        $this->addModule('BadgesModule');

        $this->render();
    }

    /**
     * Create or edit an badge.
     *
     * @since 1.0.0
     * @param string|int $badgeID
     */
    public function manage($badgeID = '') {
        $this->permission('Garden.Settings.Manage');

        // Form setup
        $this->Form->setModel($this->BadgeModel);
        $this->BadgeModel->Validation->applyRule('Name', 'Required');
        $this->BadgeModel->Validation->applyRule('Slug', 'Required');
        $this->Form->showErrors();

        $insert = (is_numeric($badgeID)) ? false : true;

        if ($badgeID) {
            $badge = $this->BadgeModel->getID($badgeID, DATASET_TYPE_ARRAY);

            // Editing an badge.
            $this->setData('Badge', $badge);
            $this->Form->setData($this->data('Badge'));

            $this->setData('HasThreshold', in_array(strtolower(val('Type', $badge)), ['reaction', 'usercount']));
        }

        $this->fireEvent('ManageBadgeForm');

        // Form submitted
        if ($this->Form->authenticatedPostBack()) {
            // Set BadgeID for existing or set Type = Manual for new
            if (!$insert) {
                $this->Form->setFormValue('BadgeID', $badgeID);
            } else {
                $this->Form->setFormValue('Type', 'Manual');
            }

            /**
             * If a Photo is added, the file details won't appear in the form values. If the field is present but empty,
             * the field was not populated. Remove it from the form to prevent wiping out an existing image.
             */
            if ($this->Form->getFormValue('Photo', false) === '') {
                $this->Form->removeFormValue('Photo');
            }

            try {
                // Upload image
                $uploadImage = new Gdn_UploadImage();

                // Validate the upload
                $tmpImage = $uploadImage->validateUpload('Photo', false);

                if ($tmpImage) {
                    // Generate the target image name.
                    $targetImage = $uploadImage->generateTargetName(PATH_UPLOADS.'/badges', '', true);
                    $basename = pathinfo($targetImage, PATHINFO_BASENAME);

                    // Delete any previously uploaded image.
                    if (isset($badge) && $badge['Photo']) {
                        $uploadImage->delete($badge['Photo']);
                    }

                    // Save the uploaded image
                    $props = $uploadImage->saveImageAs(
                        $tmpImage,
                        "badges/$basename",
                        c('Reputation.Badges.Height', 100),
                        c('Reputation.Badges.Width', 100),
                        ['SaveGif' => c('Reputation.Badges.SaveGif')]
                    );
                    $this->Form->setFormValue('Photo', sprintf($props['SaveFormat'], "badges/$basename"));
                }
            } catch (Exception $ex) {
                // Upload was optional so be quiet.
                throw $ex;
            }

            // Badge successfully saved
            $newBadgeID = $this->Form->save();
            if ($newBadgeID) {
                $badge = $this->BadgeModel->getID($newBadgeID);
                $this->setData('Badge', $badge);

                // Report success and go to list
                $badgeName = $this->Form->getFormValue('Name');
                $message = ($insert) ? t('Created new badge') : t('Updated badge');
                $message .= ' &ldquo;' . $badgeName. '&rdquo;';
                $this->informMessage($message);
                $this->setRedirectTo('/badge/all');
            }
        }

        $this->addSideMenu('/badge/all');
        $this->render();
    }

    /**
     * See who has received a badge.
     *
     * @since 1.5
     * @param string|int $badgeID Unique numeric ID or slug.
     * @param string|int $page Page number.
     */
    public function recipients($badgeID, $page = '') {
        $this->permission('Reputation.Badges.Give');

        // Page setup
        $this->title(t('Badge Recipients'));
        Gdn_Theme::section('Settings');

        // Set badge info.
        $badge = $this->BadgeModel->getID($badgeID);
        $this->setData('Badge', $badge);

        // Detect pagination.
        list($offset, $limit) = offsetLimit($page, 30);

        // Set recipient info.
        $recipients = $this->UserBadgeModel->getUsers($badgeID, ['Limit' => $limit, 'Offset' => $offset])->resultArray();
        $this->setData('Recipients', $recipients);

        // Let us page by giving result count.
        $this->setData('_CurrentRecords', count($recipients));
        // Stop paging at the right spot.
        $this->setData('_Limit', $limit);
        // Give us "of X" pager info.
        $this->setData('RecordCount', $this->UserBadgeModel->recipientCount($badgeID));

        $this->render();
    }

    /**
     * Request a badge.
     *
     * @since 1.1
     * @param mixed $badgeID Unique numeric ID or slug.
     */
    public function request($badgeID) {
        $this->MasterView = 'default';
        $this->addCssFile('style.css');
        $this->removeCssFile('admin.css');

        $this->permission('Reputation.Badges.Request');
        $session = Gdn::session();

        // Get info & confirm enabled
        $badge = $this->BadgeModel->getID($badgeID);
        $this->setData('Badge', $badge);
        if (!BadgeModel::isRequestable($badge)) {
            $this->Form->addError('Badge is not available.');
        }

        $this->Form->setModel($this->UserBadgeModel);

        if ($this->Form->authenticatedPostBack()) {
            // Add request
            $reason = $this->Form->getFormValue('Reason');
            $new = $this->UserBadgeModel->request($session->UserID, val('BadgeID', $badge), $reason);

            // Inform
            $message = ($new) ? t('Badge requested.') : t('You already requested this badge.');
            $this->informMessage($message);
        }

        $this->render();
    }

    /**
     * Current badge requests.
     *
     * @since 1.1
     */
    public function requests() {
        Gdn_Theme::section('Moderation');
        $this->permission('Reputation.Badges.Give');

        if ($this->Form->authenticatedPostBack() === true) {
            $action = $this->Form->getValue('Submit');
            $requests = $this->Form->getValue('Requests');
            $requestCount = is_array($requests) ? count($requests) : 0;
            if ($requestCount > 0 && in_array($action, ['Approve', 'Decline'])) {
                for ($i = 0; $i < $requestCount; ++$i) {
                    $data = explode('-', $requests[$i]);
                    if (count($data) != 2) {
                        continue;
                    }
                    if ($action == 'Approve') {
                        $this->UserBadgeModel->give($data[0], $data[1]);
                    } elseif ($action == 'Decline') {
                        $this->UserBadgeModel->declineRequest($data[0], $data[1]);
                    }
                }
            }
        }

        $this->RequestData = $this->UserBadgeModel->getRequests();
        Gdn::userModel()->joinUsers($this->RequestData, ['UserID']);
        $this->render('requests', 'badge');
    }

    /**
     * Revoke an badge from a user.
     *
     * @since 1.0
     * @access public
     */
    public function revoke($userID, $badgeID) {
        $this->permission('Reputation.Badges.Give');

        if ($this->Form->authenticatedPostBack() && is_numeric($userID) && is_numeric($badgeID)) {
            $this->UserBadgeModel->revoke($userID, $badgeID);
            $this->informMessage(t('Revoked badge.'));
        }

        if ($this->deliveryType() === DELIVERY_TYPE_ALL) {
            redirectTo('badge/recipients/'.$badgeID);
        }

        // Regenerate the page we take this action from.
        $this->View = 'recipients';
        $this->render();
    }
}
