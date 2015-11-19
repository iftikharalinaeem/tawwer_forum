<?php
/**
 * Badge Controller.
 *
 * @package Reputation
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
    public function Initialize() {
        parent::Initialize();
        $this->Title('Badges');
    }

    /**
     * Manage badges.
     *
     * @since 1.0.0
     * @access public
     */
    public function All() {
        $this->Permission('Garden.Settings.Manage');

//        $this->BadgeData = $this->BadgeModel->GetList();
        $this->SetData('Badges', $this->BadgeModel->GetList());

        $this->AddSideMenu('/badge/all');
        $this->Render();
    }

    /**
     * Approve badge request.
     *
     * @since 1.1
     * @access public
     */
    public function Approve($UserID = '', $BadgeID = '', $TransientKey = '') {
        $this->Permission('Reputation.Badges.Give');

        if (Gdn::Session()->ValidateTransientKey($TransientKey)) {
            $this->UserBadgeModel->Give($UserID, $BadgeID); // No reason for now
            $this->InformMessage(T('Badge request approved.'));
        }

        if ($this->_DeliveryType == DELIVERY_TYPE_BOOL) {
            return $this->Form->ErrorCount() == 0 ? true : $this->Form->Errors();
        } else {
            $this->Requests();
        }
    }

    /**
     * Decline badge request.
     *
     * @since 1.1
     * @access public
     */
    public function Decline($UserID = '', $BadgeID = '', $TransientKey = '') {
        $this->Permission('Reputation.Badges.Give');

        if (Gdn::Session()->ValidateTransientKey($TransientKey)) {
            $this->UserBadgeModel->DeclineRequest($UserID, $BadgeID);
            $this->InformMessage(T('Badge request declined.'));
        }

        if ($this->_DeliveryType == DELIVERY_TYPE_BOOL) {
            return $this->Form->ErrorCount() == 0 ? true : $this->Form->Errors();
        } else {
            $this->Requests();
        }
    }

    /**
     * Delete an badge & revoke from all users.
     *
     * @since 1.0.0
     * @access public
     */
    public function Delete($BadgeID = '') {
        $this->Permission('Garden.Settings.Manage');

        // Validate BadgeID
        if (!is_numeric($BadgeID)) {
            Redirect('/badge/all');
        }

        $Badge = $this->BadgeModel->GetID($BadgeID);
        if (!$Badge) {
            throw NotFoundException('badge');
        }

        // Form setup
        $this->Form->SetModel($this->BadgeModel);

        // Form submitted (confirmation)
        if ($this->Form->AuthenticatedPostBack()) {
            $Badge = $this->BadgeModel->GetID($BadgeID);
            if (GetValue('CanDelete', $Badge, false)) {
                // Delete & revoke
                $this->BadgeModel->Delete(array('BadgeID' => $BadgeID));
                $this->UserBadgeModel->Delete(array('BadgeID' => $BadgeID));

                // Success & redirect
                $this->InformMessage(T('Badge deleted.'));
                $this->RedirectUrl = Url('/badge/all');
            } else {
                // Failure & redirect
                $this->InformMessage(T('Badge cannot be deleted.'));
                $this->RedirectUrl = Url('/badge/all');
            }
        } else {
            // Get info for confirmation
            $this->Badge = $this->BadgeModel->GetID($BadgeID);
            if (!$this->Badge) {
                throw new Exception(T('Badge404', 'Badge not found.'), 404);
            }
        }

        $this->Render();
    }

    /**
     * Disable/enable an badge from being given. It will still show on users who have it.
     *
     * @since 1.0.0
     * @access public
     */
    public function Disable($BadgeID = '', $TransientKey = '') {
        $this->Permission('Garden.Settings.Manage');
        $Session = Gdn::Session();

        if ($Session->ValidateTransientKey($TransientKey) && is_numeric($BadgeID)) {
            // Reverse whether it's active
            $Value = (GetValue('Active', $this->BadgeModel->GetID($BadgeID))) ? 0 : 1;
            $this->BadgeModel->SetProperty($BadgeID, 'Active', $Value);
            $Message = ($Value) ? 'Badge disabled.' : 'Badge enabled.';
            $this->InformMessage($Message);
        }

        if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
            Redirect(GetIncomingValue('Target', $this->SelfUrl));
        }

        $this->SetView404();
        $this->Render();
    }

    /**
     * Give selected badge to 1 or more users.
     *
     * @since 1.0.0
     * @access public
     */
    public function Give($BadgeID = '') {
        $this->Permission('Reputation.Badges.Give');

        // Validate BadgeID
        if (!is_numeric($BadgeID)) {
            Redirect('/badge/all');
        }

        // Get info & confirm enabled
        $Badge = $this->BadgeModel->GetID($BadgeID);
        $this->SetData('Badge', $Badge);
        if (!$Badge['Active']) {
            $this->Form->AddError('Badge is not available.');
        }

        // Form setup
        $this->Form->SetModel($this->UserBadgeModel);

        // Form submitted
        if ($this->Form->AuthenticatedPostBack()) {
            // Set BadgeID
            $this->Form->SetFormValue('BadgeID', $BadgeID);

            // Get reason
            $Reason = $this->Form->GetFormValue('Reason');

            // Set recipients
            $RecipientUserIDs = array();
            $To = explode(',', $this->Form->GetFormValue('To', ''));
            $UserModel = new UserModel();
            $Result = true;
            foreach ($To as $Name) {
                if (trim($Name) != '') {
                    $User = $UserModel->GetByUsername(trim($Name));
                    if (is_object($User)) {
                        $this->Form->SetFormValue('UserID', $User->UserID);
                        $Saved = $this->UserBadgeModel->Give($User->UserID, $BadgeID, $Reason);
                        $Result = $Result && $Saved;
                        $this->Form->SetValidationResults($this->UserBadgeModel->Validation->Results());
                        $this->UserBadgeModel->Validation->Results(true);
                    }
                }
            }
            $this->Form->SetFormValue('DateCompleted', date('Y-m-d H:i:s'));

            // Give to named users
            if ($Result) {
                $this->InformMessage(T('Gave badge to users.'));
                $this->RedirectUrl = Url('/badge/all');
            }
        }

        $this->Render();
    }

    /**
     * Give any badge to selected user.
     *
     * @since 1.0.0
     * @access public
     */
    public function GiveUser($UserID = '') {
        $this->Permission('Reputation.Badges.Give');

        // Validate $UserID
        if (empty($UserID)) {
            $PostUserID = Gdn::Request()->Post('UserID', false);
            if (is_null($PostUserID)) {
                throw NotFoundException('UserID');
            }

            $UserID = $PostUserID;
        }

        // Get user data
        $UserModel = new UserModel();
        $this->SetData('User', $UserModel->GetID($UserID), true);
        if (!$this->Data('User')) {
            throw NotFoundException('User');
        }

        // Form setup
        $this->Form->SetModel($this->UserBadgeModel);

        // Form submitted
        if ($this->Form->IsPostBack()) {
            // Validate badge by getting its data
            $BadgeID = $this->Form->GetFormValue('BadgeID');
            $Badge = $this->BadgeModel->GetID($BadgeID);
            $Reason = $this->Form->GetFormValue('Reason');

            if ($Badge) {
                // Give Badge
                $Saved = $this->UserBadgeModel->Give($this->User->UserID, $BadgeID, $Reason);
                $this->SetData('Awarded', $Saved);

                $this->Form->SetValidationResults($this->UserBadgeModel->Validation->Results());
                $this->UserBadgeModel->Validation->Results(true);

                // Continue
                if ($Saved) {
                    $UserBadge = $this->UserBadgeModel->GetID($UserID, $BadgeID);
                    $OutputBadge = array_merge((array)$Badge, (array)$UserBadge);
                    $this->SetData('Badge', $OutputBadge);

                    $this->InformMessage(T('Gave badge to user.'));
                    $this->RedirectUrl = Url('profile/'.$UserID.'/'.GetValue('Name', $this->User));
                }
            } else {
                throw NotFoundException('Badge');
            }
        }

        // Get badge list for dropdown
        $this->BadgeData = $this->BadgeModel->GetMenu();
        $this->Render();
    }

    /**
     * Hide/Unhide an badge from being listed. It will still show on users who have it.
     *
     * @since 1.0.0
     * @access public
     */
    public function Hide($BadgeID = '', $TransientKey = '') {
        $this->Permission('Garden.Settings.Manage');
        $Session = Gdn::Session();

        if ($Session->ValidateTransientKey($TransientKey) && is_numeric($BadgeID)) {
            // Reverse visibility
            $Value = ($this->BadgeModel->GetID($BadgeID)->Visible) ? 0 : 1;
            $this->BadgeModel->SetProperty($BadgeID, 'Visible', $Value);
            $Message = ($Value) ? 'Badge unhidden.' : 'Badge hidden.';
            $this->InformMessage($Message);
        }

        if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
            Redirect(GetIncomingValue('Target', $this->SelfUrl));
        }

        $this->SetView404();
        $this->Render();
    }

    /**
     * View a badge.
     *
     * @since 1.0.0
     * @access public
     */
    public function Index($BadgeID = '', $Name = '') {

        // Get badge data or 404
        $this->Badge = $this->BadgeModel->GetID($BadgeID);
        if (!$this->Badge) {
            throw new Exception(T('Badge404', 'Badge not found.'), 404);
        }
        $this->SetData('Badge', $this->Badge);

        // Current user a recipient?
        $this->UserBadge = false;
        if (Gdn::Session()->IsValid()) {
            $this->UserBadge = $this->UserBadgeModel->GetID(Gdn::Session()->User->UserID, $this->Data('Badge.BadgeID'));
        }
        $this->SetData('UserBadge', $this->UserBadge);

        // Get recipients
        $this->SetData('Recipients', $this->UserBadgeModel->GetUsers($BadgeID, array('Limit' => 15))->ResultArray());
        $this->SetData('BadgeID', $BadgeID, true);
        if (GetValue('_New', $this->UserBadge) && GetValue('Type', $this->Badge) == 'Manual') {
            $this->AddModule('RequestBadgeModule');
        }
        $this->AddModule('BadgesModule');

        $this->Render();
    }

    /**
     * Create or edit an badge.
     *
     * @since 1.0.0
     * @access public
     */
    public function Manage($BadgeID = '') {
        $this->Permission('Garden.Settings.Manage');

        // Form setup
        $this->Form->SetModel($this->BadgeModel);
        $this->Form->ShowErrors();

        $Insert = (is_numeric($BadgeID)) ? false : true;

        if ($BadgeID) {
            $Badge = $this->BadgeModel->GetID($BadgeID, DATASET_TYPE_ARRAY);

            // Editing an badge.
            $this->SetData('Badge', $Badge);
            $this->Form->SetData($this->Data('Badge'));
        }

        // Form submitted
        if ($this->Form->AuthenticatedPostBack()) {
            $Data = $this->Form->FormValues();

            // Set BadgeID for existing or set Type = Manual for new
            if (!$Insert) {
                $this->Form->SetFormValue('BadgeID', $BadgeID);
            } else {
                $this->Form->SetFormValue('Type', 'Manual');
            }

            try {
                    // Upload image
                    $UploadImage = new Gdn_UploadImage();

                    // Validate the upload
                    $TmpImage = $UploadImage->ValidateUpload('Photo', false);

                if ($TmpImage) {
                    // Generate the target image name.
                    $TargetImage = $UploadImage->GenerateTargetName(PATH_UPLOADS.'/badges', '', true);
                    $Basename = pathinfo($TargetImage, PATHINFO_BASENAME);

                    // Delete any previously uploaded image.
                    if (isset($Badge) && $Badge['Photo']) {
                        $UploadImage->Delete($Badge['Photo']);
                    }

                    // Save the uploaded image
                    $Props = $UploadImage->SaveImageAs(
                        $TmpImage,
                        "badges/$Basename",
                        C('Reputation.Badges.Height', 100),
                        C('Reputation.Badges.Width', 100),
                        array('SaveGif' => C('Reputation.Badges.SaveGif'))
                    );
                    $this->Form->SetFormValue('Photo', sprintf($Props['SaveFormat'], "badges/$Basename"));
                }
            } catch (Exception $Ex) {
                // Upload was optional so be quiet.
                throw $Ex;
            }

            // Badge successfully saved
            $NewBadgeID = $this->Form->Save();
            if ($NewBadgeID) {
                $Badge = $this->BadgeModel->GetID($NewBadgeID);
                $this->SetData('Badge', $Badge);

                // Report success and go to list
                $BadgeName = $this->Form->GetFormValue('Name');
                $Message = ($Insert) ? T('Created new badge') : T('Updated badge');
                $Message .= ' &ldquo;' . $BadgeName. '&rdquo;';
                $this->InformMessage($Message);
                $this->RedirectUrl = Url('/badge/all');
            }
        }

        $this->AddSideMenu('/badge/all');
        $this->Render();
    }

    /**
     * Request a badge.
     *
     * @since 1.1
     * @access public
     * @param mixed $BadgeID Unique numeric ID or slug.
     */
    public function Request($BadgeID) {
        $this->Permission('Reputation.Badges.Request');
        $Session = Gdn::Session();

        // Get info & confirm enabled
        $Badge = $this->BadgeModel->GetID($BadgeID);
        $this->SetData('Badge', $Badge);
        if (!$Badge['Active']) {
            $this->Form->AddError('Badge is not available.');
        }

        $this->Form->SetModel($this->UserBadgeModel);

        if ($this->Form->AuthenticatedPostBack()) {
            // Add request
            $Reason = $this->Form->GetFormValue('Reason');
            $New = $this->UserBadgeModel->Request($Session->UserID, GetValue('BadgeID', $Badge), $Reason);

            // Inform
            $Message = ($New) ? T('Badge requested.') : T('You already requested this badge.');
            $this->InformMessage($Message);
        }

        $this->Render();
    }

    /**
     * Current badge requests.
     *
     * @since 1.1
     * @access public
     */
    public function Requests() {
        $this->Permission('Reputation.Badges.Give');
        $Session = Gdn::Session();

        $this->RequestData = $this->UserBadgeModel->GetRequests();
        Gdn::UserModel()->JoinUsers($this->RequestData, array('UserID'));

        if ($this->Form->AuthenticatedPostBack() === true) {
            $Action = $this->Form->GetValue('Submit');
            $Requests = $this->Form->GetValue('Requests');
            $RequestCount = is_array($Requests) ? count($Requests) : 0;
            if ($RequestCount > 0 && in_array($Action, array('Approve', 'Decline'))) {
                for ($i = 0; $i < $RequestCount; ++$i) {
                    $Data = explode('-', $Requests[$i]);
                    if (count($Data) != 2) {
                        continue;
                    }
                    if ($Action == 'Approve') {
                        $this->UserBadgeModel->Give($Data[0], $Data[1]);
                    } elseif ($Action == 'Decline')
                        $this->UserBadgeModel->DeclineRequest($Data[0], $Data[1]);
                }
            }
        }

        $this->AddSideMenu('reputation/badge/requests');
        $this->View = 'requests';
        $this->Render();
    }

    /**
     * Revoke an badge from a user.
     *
     * @since 1.0
     * @access public
     */
    public function Revoke($UserID, $BadgeID, $TransientKey = '') {
        $this->Permission('Garden.Settings.Manage');
        $Session = Gdn::Session();

        if ($this->Form->IsPostBack() && is_numeric($UserID) && is_numeric($BadgeID)) {
            $UserID = $this->UserBadgeModel->Revoke($UserID, $BadgeID);
            $this->InformMessage(T('Revoked badge.'));
            Redirect('profile/badges/'.$UserID.'/x');
        }

        $this->SetView404();
        $this->Render();
    }
}
