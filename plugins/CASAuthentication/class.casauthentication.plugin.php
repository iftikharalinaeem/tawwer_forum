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
    * @param Gdn_Controller $Sender
    */
    public function settingsController_CAS_create($Sender) {
        $Cf = new ConfigurationModule($Sender);
        $Cf->initialize(array(
            'Plugins.CASAuthentication.Host',
            'Plugins.CASAuthentication.Context' => array('Control' => 'TextBox', 'Default' => '/cas'),
            'Plugins.CASAuthentication.Port' => array('Control' => 'TextBox', 'Default' => 443),
            'Plugins.CASAuthentication.ProfileUrl' => array('Control' => 'TextBox'),
            'Plugins.CASAuthentication.RegisterUrl' => array('Control' => 'TextBox')
        ));
        $Sender->addSideMenu();
        $Sender->title('CAS Settings');
        $Cf->renderAll();
    }

}
