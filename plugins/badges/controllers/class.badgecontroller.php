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
 * @package Reputation
 *
 * @todo Points
 * @todo Secret
 * @todo Requestable
 * @todo Graduated abilities
 */
class BadgeController extends BadgesAppController {
    /**
     * Before any call to the controller.
     *
     * @since 1.0.0
     * @access public
     */
    public function initialize() {
        parent::initialize();
        $this->title('Badges');
    }

    /**
     * Manage badges.
     *
     * @since 1.0.0
     * @access public
     */
    public function all() {
        $this->permission('Garden.Settings.Manage');
        $this->setData('Badges', $this->BadgeModel->getList());

        $this->addSideMenu('/badge/all');
        $this->render();
    }

    /**
     * Approve badge request.
     *
     * @since 1.1
     * @access public
     */
    public function approve($UserID = '', $BadgeID = '', $TransientKey = '') {
        $this->permission('Reputation.Badges.Give');

        if (Gdn::session()->validateTransientKey($TransientKey)) {
            $this->UserBadgeModel->give($UserID, $BadgeID); // No reason for now
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
     * @access public
     */
    public function decline($UserID = '', $BadgeID = '', $TransientKey = '') {
        $this->permission('Reputation.Badges.Give');

        if (Gdn::session()->validateTransientKey($TransientKey)) {
            $this->UserBadgeModel->declineRequest($UserID, $BadgeID);
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
     * @access public
     */
    public function delete($BadgeID = '') {
        $this->permission('Garden.Settings.Manage');

        // Validate BadgeID
        if (!is_numeric($BadgeID)) {
            redirect('/badge/all');
        }

        $Badge = $this->BadgeModel->getID($BadgeID);
        if (!$Badge) {
            throw NotFoundException('badge');
        }

        // Form setup
        $this->Form->setModel($this->BadgeModel);

        // Form submitted (confirmation)
        if ($this->Form->authenticatedPostBack()) {
            $Badge = $this->BadgeModel->getID($BadgeID);
            if (val('CanDelete', $Badge, false)) {
                // Delete & revoke
                $this->BadgeModel->delete(array('BadgeID' => $BadgeID));
                $this->UserBadgeModel->delete(array('BadgeID' => $BadgeID));

                // Success & redirect
                $this->informMessage(t('Badge deleted.'));
                $this->RedirectUrl = url('/badge/all');
            } else {
                // Failure & redirect
                $this->informMessage(t('Badge cannot be deleted.'));
                $this->RedirectUrl = url('/badge/all');
            }
        } else {
            // Get info for confirmation
            $this->Badge = $this->BadgeModel->getID($BadgeID);
            if (!$this->Badge) {
                throw new Exception(t('Badge404', 'Badge not found.'), 404);
            }
        }

        $this->render();
    }

    /**
     * Disable/enable an badge from being given. It will still show on users who have it.
     *
     * @since 1.0.0
     * @access public
     */
    public function disable($BadgeID = '', $TransientKey = '') {
        $this->permission('Garden.Settings.Manage');
        $Session = Gdn::session();

        if ($Session->validateTransientKey($TransientKey) && is_numeric($BadgeID)) {
            // Reverse whether it's active
            $Value = (val('Active', $this->BadgeModel->getID($BadgeID))) ? 0 : 1;
            $this->BadgeModel->setProperty($BadgeID, 'Active', $Value);
            $Message = ($Value) ? 'Badge disabled.' : 'Badge enabled.';
            $this->informMessage($Message);
        }

        if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
            redirect(getIncomingValue('Target', $this->SelfUrl));
        }

        $this->setView404();
        $this->render();
    }

    /**
     * Give selected badge to 1 or more users.
     *
     * @since 1.0.0
     * @access public
     */
    public function give($BadgeID = '') {
        $this->permission('Reputation.Badges.Give');

        // Validate BadgeID
        if (!is_numeric($BadgeID)) {
            redirect('/badge/all');
        }

        // Get info & confirm enabled
        $Badge = $this->BadgeModel->getID($BadgeID);
        $this->setData('Badge', $Badge);
        if (!$Badge['Active']) {
            $this->Form->addError('Badge is not available.');
        }

        // Form setup
        $this->Form->setModel($this->UserBadgeModel);

        // Form submitted
        if ($this->Form->authenticatedPostBack()) {
            // Set BadgeID
            $this->Form->setFormValue('BadgeID', $BadgeID);

            // Get reason
            $Reason = $this->Form->getFormValue('Reason');

            // Set recipients
            $RecipientUserIDs = array();
            $To = explode(',', $this->Form->getFormValue('To', ''));
            $UserModel = new UserModel();
            $Result = true;
            foreach ($To as $Name) {
                if (trim($Name) != '') {
                    $User = $UserModel->getByUsername(trim($Name));
                    if (is_object($User)) {
                        $this->Form->setFormValue('UserID', $User->UserID);
                        $Saved = $this->UserBadgeModel->give($User->UserID, $BadgeID, $Reason);
                        $Result = $Result && $Saved;
                        $this->Form->setValidationResults($this->UserBadgeModel->Validation->results());
                        $this->UserBadgeModel->Validation->results(true);
                    }
                }
            }
            $this->Form->setFormValue('DateCompleted', date('Y-m-d H:i:s'));

            // Give to named users
            if ($Result) {
                $this->informMessage(t('Gave badge to users.'));
                $this->RedirectUrl = url('/badge/all');
            }
        }

        $this->render();
    }

    /**
     * Give any badge to selected user.
     *
     * @since 1.0.0
     * @access public
     */
    public function giveUser($UserID = '') {
        $this->permission('Reputation.Badges.Give');

        // Validate $UserID
        if (empty($UserID)) {
            $PostUserID = Gdn::request()->post('UserID', false);
            if (is_null($PostUserID)) {
                throw NotFoundException('UserID');
            }

            $UserID = $PostUserID;
        }

        // Get user data
        $UserModel = new UserModel();
        $this->setData('User', $UserModel->getID($UserID), true);
        if (!$this->data('User')) {
            throw NotFoundException('User');
        }

        // Form setup
        $this->Form->setModel($this->UserBadgeModel);

        // Form submitted
        if ($this->Form->authenticatedPostBack()) {
            // Validate badge by getting its data
            $BadgeID = $this->Form->getFormValue('BadgeID');
            $Badge = $this->BadgeModel->getID($BadgeID);
            $Reason = $this->Form->getFormValue('Reason');

            if ($Badge) {
                // Give Badge
                $Saved = $this->UserBadgeModel->give($this->User->UserID, $BadgeID, $Reason);
                $this->setData('Awarded', $Saved);

                $this->Form->setValidationResults($this->UserBadgeModel->Validation->Results());
                $this->UserBadgeModel->Validation->results(true);

                // Continue
                if ($Saved) {
                    $UserBadge = $this->UserBadgeModel->getByUser($UserID, $BadgeID);
                    $OutputBadge = array_merge((array)$Badge, (array)$UserBadge);
                    $this->setData('Badge', $OutputBadge);

                    $this->informMessage(t('Gave badge to user.'));
                    $this->RedirectUrl = url('profile/'.$UserID.'/'.val('Name', $this->User));
                }
            } else {
                throw NotFoundException('Badge');
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
     * @access public
     */
    public function hide($BadgeID = '', $TransientKey = '') {
        $this->permission('Garden.Settings.Manage');
        $Session = Gdn::session();

        if ($Session->validateTransientKey($TransientKey) && is_numeric($BadgeID)) {
            // Reverse visibility
            $Value = ($this->BadgeModel->getID($BadgeID)->Visible) ? 0 : 1;
            $this->BadgeModel->setProperty($BadgeID, 'Visible', $Value);
            $Message = ($Value) ? 'Badge unhidden.' : 'Badge hidden.';
            $this->informMessage($Message);
        }

        if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
            redirect(getIncomingValue('Target', $this->SelfUrl));
        }

        $this->setView404();
        $this->render();
    }

    /**
     * View a badge.
     *
     * @since 1.0.0
     * @access public
     */
    public function index($BadgeID = '', $Name = '') {

        // Get badge data or 404
        $this->Badge = $this->BadgeModel->getID($BadgeID);
        if (!$this->Badge) {
            throw new Exception(t('Badge404', 'Badge not found.'), 404);
        }
        $this->setData('Badge', $this->Badge);

        // Current user a recipient?
        $this->UserBadge = false;
        if (Gdn::session()->isValid()) {
            $this->UserBadge = $this->UserBadgeModel->getByUser(Gdn::session()->User->UserID, $this->data('Badge.BadgeID'));
        }
        $this->SetData('UserBadge', $this->UserBadge);

        // Get recipients
        $this->setData('Recipients', $this->UserBadgeModel->getUsers($BadgeID, array('Limit' => 15))->resultArray());
        $this->setData('BadgeID', $BadgeID, true);
        if (GetValue('_New', $this->UserBadge) && GetValue('Type', $this->Badge) == 'Manual') {
            $this->addModule('RequestBadgeModule');
        }
        $this->addModule('BadgesModule');

        $this->render();
    }

    /**
     * Create or edit an badge.
     *
     * @since 1.0.0
     * @access public
     */
    public function manage($BadgeID = '') {
        $this->permission('Garden.Settings.Manage');

        // Form setup
        $this->Form->wrapElements = true;
        $this->Form->setModel($this->BadgeModel);
        $this->Form->showErrors();

        $Insert = (is_numeric($BadgeID)) ? false : true;

        if ($BadgeID) {
            $Badge = $this->BadgeModel->getID($BadgeID, DATASET_TYPE_ARRAY);

            // Editing an badge.
            $this->setData('Badge', $Badge);
            $this->Form->setData($this->data('Badge'));
        }

        // Form submitted
        if ($this->Form->authenticatedPostBack()) {
            $Data = $this->Form->formValues();

            // Set BadgeID for existing or set Type = Manual for new
            if (!$Insert) {
                $this->Form->setFormValue('BadgeID', $BadgeID);
            } else {
                $this->Form->setFormValue('Type', 'Manual');
            }

            try {
                    // Upload image
                    $UploadImage = new Gdn_UploadImage();

                    // Validate the upload
                    $TmpImage = $UploadImage->validateUpload('Photo', false);

                if ($TmpImage) {
                    // Generate the target image name.
                    $TargetImage = $UploadImage->generateTargetName(PATH_UPLOADS.'/badges', '', true);
                    $Basename = pathinfo($TargetImage, PATHINFO_BASENAME);

                    // Delete any previously uploaded image.
                    if (isset($Badge) && $Badge['Photo']) {
                        $UploadImage->delete($Badge['Photo']);
                    }

                    // Save the uploaded image
                    $Props = $UploadImage->saveImageAs(
                        $TmpImage,
                        "badges/$Basename",
                        C('Reputation.Badges.Height', 100),
                        C('Reputation.Badges.Width', 100),
                        array('SaveGif' => C('Reputation.Badges.SaveGif'))
                    );
                    $this->Form->setFormValue('Photo', sprintf($Props['SaveFormat'], "badges/$Basename"));
                }
            } catch (Exception $Ex) {
                // Upload was optional so be quiet.
                throw $Ex;
            }

            // Badge successfully saved
            $NewBadgeID = $this->Form->save();
            if ($NewBadgeID) {
                $Badge = $this->BadgeModel->getID($NewBadgeID);
                $this->setData('Badge', $Badge);

                // Report success and go to list
                $BadgeName = $this->Form->getFormValue('Name');
                $Message = ($Insert) ? t('Created new badge') : t('Updated badge');
                $Message .= ' &ldquo;' . $BadgeName. '&rdquo;';
                $this->informMessage($Message);
                $this->RedirectUrl = url('/badge/all');
            }
        }

        $this->addSideMenu('/badge/all');
        $this->render();
    }

    /**
     * Request a badge.
     *
     * @since 1.1
     * @access public
     * @param mixed $BadgeID Unique numeric ID or slug.
     */
    public function request($BadgeID) {
        $this->permission('Reputation.Badges.Request');
        $Session = Gdn::session();

        // Get info & confirm enabled
        $Badge = $this->BadgeModel->getID($BadgeID);
        $this->setData('Badge', $Badge);
        if (!$Badge['Active']) {
            $this->Form->addError('Badge is not available.');
        }

        $this->Form->setModel($this->UserBadgeModel);

        if ($this->Form->authenticatedPostBack()) {
            // Add request
            $Reason = $this->Form->getFormValue('Reason');
            $New = $this->UserBadgeModel->request($Session->UserID, val('BadgeID', $Badge), $Reason);

            // Inform
            $Message = ($New) ? t('Badge requested.') : t('You already requested this badge.');
            $this->informMessage($Message);
        }

        $this->render();
    }

    /**
     * Current badge requests.
     *
     * @since 1.1
     * @access public
     */
    public function requests() {
        $this->permission('Reputation.Badges.Give');

        $this->RequestData = $this->UserBadgeModel->getRequests();
        Gdn::userModel()->joinUsers($this->RequestData, array('UserID'));

        if ($this->Form->authenticatedPostBack() === true) {
            $Action = $this->Form->getValue('Submit');
            $Requests = $this->Form->getValue('Requests');
            $RequestCount = is_array($Requests) ? count($Requests) : 0;
            if ($RequestCount > 0 && in_array($Action, array('Approve', 'Decline'))) {
                for ($i = 0; $i < $RequestCount; ++$i) {
                    $Data = explode('-', $Requests[$i]);
                    if (count($Data) != 2) {
                        continue;
                    }
                    if ($Action == 'Approve') {
                        $this->UserBadgeModel->give($Data[0], $Data[1]);
                    } elseif ($Action == 'Decline')
                        $this->UserBadgeModel->declineRequest($Data[0], $Data[1]);
                }
            }
        }

        $this->addSideMenu('reputation/badge/requests');
        $this->View = 'requests';
        $this->render();
    }

    /**
     * Revoke an badge from a user.
     *
     * @since 1.0
     * @access public
     */
    public function revoke($UserID, $BadgeID) {
        $this->permission('Garden.Settings.Manage');

        if ($this->Form->authenticatedPostBack() && is_numeric($UserID) && is_numeric($BadgeID)) {
            $UserID = $this->UserBadgeModel->revoke($UserID, $BadgeID);
            $this->informMessage(T('Revoked badge.'));
            redirect('profile/badges/'.$UserID.'/x');
        }

        $this->setView404();
        $this->render();
    }
}
