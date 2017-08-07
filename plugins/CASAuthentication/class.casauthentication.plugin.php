<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Class CASAuthenticationPlugin
 */
class CASAuthenticationPlugin extends Gdn_Plugin {

    /**
    * Startup.
    */
    public function initializeCAS() {
        require_once dirname(__FILE__).'/CAS.php';

        $Host = c('Plugins.CASAuthentication.Host');
        $Port = (int)c('Plugins.CASAuthentication.Port', 443);
        $Context = c('Plugins.CASAuthentication.Context', '/cas');

        // Initialize phpCAS.
        phpCAS::client(CAS_VERSION_1_0, $Host, $Port, $Context);
        phpCAS::setNoCasServerValidation();
        phpCAS::setNoClearTicketsFromUrl(false);

        $Url = url('/entry/cas', true);
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
    * @param Gdn_Controller $sender
    * @param array $args
    */
    public function base_connectData_handler($sender, $args) {
        if (val(0, $args) != 'cas') {
            return;
        }

        $user = Gdn::session()->stash('CASUser');

        if (!$user) {
            $url = url('/entry/cas');
            $message = "There was an error retrieving your user data. Click <a href='$url'>here</a> to try again.";
            throw new Gdn_UserException($message);
        }

        // Make sure there is a user.

        $form = $sender->Form;
        $form->setFormValue('UniqueID', $user['UniqueID']);
        $form->setFormValue('Provider', 'cas');
        $form->setFormValue('ProviderName', 'CRN');
        $form->setFormValue('Name', $user['Name']);
        $form->setFormValue('FullName', $user['FirstName'].' '.$user['LastName']);
        $form->setFormValue('Email', $user['Email']);

        saveToConfig([
            'Garden.User.ValidationRegex' => UserModel::USERNAME_REGEX_MIN,
            'Garden.User.ValidationLength' => '{3,50}',
            'Garden.Registration.NameUnique' => false,
            'Garden.Registration.AutoConnect' => true
        ], '', false);

        // Save some original data in the attributes of the connection for later API calls.
        $attributes = [
            'FirstName' => $user['FirstName'],
            'LastName' => $user['LastName']
        ];
        $form->setFormValue('Attributes', $attributes);

        $sender->setData('Verified', true);
    }

    /**
    *
    *
    * @param EntryController $sender
    */
    public function entryController_cAS_create($sender) {
        $this->initializeCAS();

        // force CAS authentication
        try {
            unset($_GET['rm']);
            phpCAS::forceAuthentication();
        } catch (Exception $ex) {
            decho($ex);
            die();
        }

        $email = phpCAS::getUser();
        if (!$email) {
            die('Failed');
        } else {
            // We now have a user so we need to get some info.
            $url = sprintf(c('Plugins.CASAuthentication.ProfileUrl'), urlencode($email));
            $data = file_get_contents($url);

            $xml = (array)simplexml_load_string($data);

            $user = arrayTranslate($xml, ['email' => 'Email', 'nickname' => 'Name', 'firstName' => 'FirstName', 'lastName' => 'LastName']);
            $user['UniqueID'] = $user['Email'];
            Gdn::session()->stash('CASUser', $user);

            // Now that we have the user we can redirect.
            $get = $sender->Request->get();
            unset($get['ticket']);
            $url = '/entry/connect/cas?'.http_build_query($get);
            redirectTo($url);
        }
    }

    /**
    *
    *
    * @param $sender
    */
    public function entryController_register_handler($sender) {
        $url = c('Plugins.CASAuthentication.RegisterUrl');
        redirectTo($url, 302, false);
    }

    /**
    *
    *
    * @param EntryController $sender
    */
    public function entryController_signIn_handler($sender) {
        if ($sender->deliveryType() == DELIVERY_TYPE_ALL) {
            $get = $sender->Request->get();
            $url = '/entry/cas?'.http_build_query($get);
            redirectTo($url);
        }
    }

    /**
    *
    *
    * @param $sender
    */
    public function entryController_signOut_handler($sender) {
        $this->initializeCAS();
        phpCAS::logout();
    }

    /**
    *
    * @param SettingsController $sender
    */
    public function settingsController_cAS_create($sender) {
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
