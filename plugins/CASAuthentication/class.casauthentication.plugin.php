<?php
/**
 * @copyright Copyright 2008-2016 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['CASAuthentication'] = array(
    'Name' => 'CAS Authentication for Vanilla',
    'Description' => 'Allows Vanilla to authenticate against a <a href="http://en.wikipedia.org/wiki/Central_Authentication_Service">CAS</a> authentication server.',
    'Version' => '1.1',
    'RequiredApplications' => array('Vanilla' => '2.1'),
    'MobileFriendly' => true,
    'Author' => 'Todd Burry',
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
    'SettingsUrl' => '/settings/cas',
    'SettingsPermission' => 'Garden.Settings.Manage',
);

/**
 * Class CASAuthenticationPlugin
 */
class CASAuthenticationPlugin extends Gdn_Plugin {

    /**
    * Startup.
    */
    public function initializeCAS() {
        require_once dirname(__FILE__).'/CAS.php';

        $Host = C('Plugins.CASAuthentication.Host');
        $Port = (int)C('Plugins.CASAuthentication.Port', 443);
        $Context = C('Plugins.CASAuthentication.Context', '/cas');

        // Initialize phpCAS.
        phpCAS::client(CAS_VERSION_1_0, $Host, $Port, $Context);
        phpCAS::setNoCasServerValidation();
        phpCAS::setNoClearTicketsFromUrl(false);

        $Url = Url('/entry/cas', true);
        if (Gdn::request()->get('Target')) {
            $Url .= '?Target='.urlencode(Gdn::request()->get('Target'));
        }
        phpCAS::setFixedServiceURL($Url);
    }

    /**
    *
    */
    public function setup() {
        saveToConfig('Garden.SignIn.Popup', false);
    }

    /**
    * @param Gdn_Controller $Sender
    * @param array $Args
    */
    public function base_connectData_handler($Sender, $Args) {
        if (val(0, $Args) != 'cas') {
            return;
        }

        $User = Gdn::session()->stash('CASUser');

        if (!$User) {
            $Url = Url('/entry/cas');
            $Message = "There was an error retrieving your user data. Click <a href='$Url'>here</a> to try again.";
            throw new Gdn_UserException($Message);
        }

        // Make sure there is a user.

        $Form = $Sender->Form;
        $Form->setFormValue('UniqueID', $User['UniqueID']);
        $Form->setFormValue('Provider', 'cas');
        $Form->setFormValue('ProviderName', 'CRN');
        $Form->setFormValue('Name', $User['Name']);
        $Form->setFormValue('FullName', $User['FirstName'].' '.$User['LastName']);
        $Form->setFormValue('Email', $User['Email']);

        saveToConfig(array(
            'Garden.User.ValidationRegex' => UserModel::USERNAME_REGEX_MIN,
            'Garden.User.ValidationLength' => '{3,50}',
            'Garden.Registration.NameUnique' => false,
            'Garden.Registration.AutoConnect' => true
        ), '', false);

        // Save some original data in the attributes of the connection for later API calls.
        $Attributes = array(
            'FirstName' => $User['FirstName'],
            'LastName' => $User['LastName']
        );
        $Form->setFormValue('Attributes', $Attributes);

        $Sender->setData('Verified', true);
    }

    /**
    *
    *
    * @param EntryController $Sender
    */
    public function entryController_CAS_create($Sender) {
        $this->initializeCAS();

        // force CAS authentication
        try {
            unset($_GET['rm']);
            phpCAS::forceAuthentication();
        } catch (Exception $Ex) {
            decho($Ex);
            die();
        }

        $Email = phpCAS::getUser();
        if (!$Email) {
            die('Failed');
        } else {
            // We now have a user so we need to get some info.
            $Url = sprintf(C('Plugins.CASAuthentication.ProfileUrl'), urlencode($Email));
            $Data = file_get_contents($Url);

            $Xml = (array)simplexml_load_string($Data);

            $User = ArrayTranslate($Xml, array('email' => 'Email', 'nickname' => 'Name', 'firstName' => 'FirstName', 'lastName' => 'LastName'));
            $User['UniqueID'] = $User['Email'];
            Gdn::session()->stash('CASUser', $User);

            // Now that we have the user we can redirect.
            $Get = $Sender->Request->get();
            unset($Get['ticket']);
            $Url = '/entry/connect/cas?'.http_build_query($Get);
            redirect($Url);
        }
    }

    /**
    *
    *
    * @param $Sender
    */
    public function entryController_register_handler($Sender) {
        $Url = c('Plugins.CASAuthentication.RegisterUrl');
        redirect($Url);
    }

    /**
    *
    *
    * @param EntryController $Sender
    */
    public function entryController_signIn_handler($Sender) {
        if ($Sender->deliveryType() == DELIVERY_TYPE_ALL) {
            $Get = $Sender->Request->get();
            $Url = '/entry/cas?'.http_build_query($Get);
            Redirect($Url);
        }
    }

    /**
    *
    *
    * @param $Sender
    */
    public function entryController_signOut_handler($Sender) {
        $this->initializeCAS();
        phpCAS::logout();
    }

    /**
    *
    * @param SettingsController $sender
    */
    public function settingsController_CAS_create($sender) {
        $sender->addSideMenu();
        $sender->title('CAS Settings');

        // Prevent non-admins from accessing this page
        $sender->permission('Garden.Settings.Manage');
        $sender->setData('PluginDescription', $this->getPluginKey('Description'));

        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField([
            'Plugins.CASAuthentication.Host',
            'Plugins.CASAuthentication.Context' => c('Plugins.CASAuthentication.Context', '/cas'),
            'Plugins.CASAuthentication.Port' => c('Plugins.CASAuthentication.Port', 443),
            'Plugins.CASAuthentication.ProfileUrl',
            'Plugins.CASAuthentication.RegisterUrl',
        ]);

        $sender->Form->setModel($configurationModel);

        // If seeing the form for the first time...
        if ($sender->Form->authenticatedPostBack() === false) {
            $sender->Form->setData($configurationModel->Data);
        } else {
            $configurationModel->Validation->applyRule('Plugins.CASAuthentication.Host', 'Required');
            $configurationModel->Validation->applyRule('Plugins.CASAuthentication.Context', 'Required');
            $configurationModel->Validation->applyRule('Plugins.CASAuthentication.Port', 'Required');
            $configurationModel->Validation->applyRule('Plugins.CASAuthentication.ProfileUrl', 'Required');
            $configurationModel->Validation->applyRule('Plugins.CASAuthentication.RegisterUrl', 'Required');

            if ($sender->Form->save()) {
                $sender->informMessage(t('Your changes have been saved.'));
            }
        }

        $sender->render($this->getView('configuration.php'));
    }
}
