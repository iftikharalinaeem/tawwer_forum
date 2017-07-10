<?php
class SAMLSSOController extends PluginController {

    /**
     * SAMLSSOController constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->Form = new Gdn_Form();
    }

    /**
     * Add endpoint.
     */
    public function add() {
        $this->edit();
    }

    /**
     * Edit endpoint
     *
     * @param string $authenticationKey SAML authentication key
     */
    public function edit($authenticationKey = '') {
        $this->permission('Garden.Settings.Manage');

        $this->setData('AuthenticationKey', $authenticationKey);

        $model = new Gdn_AuthenticationProviderModel();
        $form = $this->Form;
        $form->setModel($model);

        if ($authenticationKey !== null) {
            $provider = Gdn_AuthenticationProviderModel::getProviderByKey($authenticationKey);
            $form->setData($provider);
        }

        // Set up the form.
        $formStructure = [
            'AuthenticationKey' => [
                'LabelCode' => 'Connection ID',
                'Description' => t('The connection ID uniquely identifies this connection. (Letters and digits only)'),
            ],
            'EntityID' => [
                'LabelCode' => 'Entity ID',
                'Description' => t('Globally unique name of the SAML entity.'),
            ],
            'Name' => [
                'LabelCode' => 'Site Name',
                'Description' => t('Enter a short name for the site.', 'Enter a short name for the site. This is displayed on the signin buttons.'),
            ],
            'SignInUrl' => [
                'LabelCode' => 'Sign In URL',
                'Description' => t('The url that users use to sign in.').' '.t('Use {target} to specify a redirect.'),
            ],
            'SignOutUrl' => [
                'LabelCode' => 'Sign Out URL',
                'Description' => t('The url that users use to sign out of your site.')
            ],
            'RegisterUrl' => [
                'LabelCode' => 'Registration URL',
                'Description' => t('The url that users use to register for a new account.')
            ],
            'KeyMap[mail]' => [
                'LabelCode' => 'Email',
                'Description' => t('The Key in the XML payload to designate Emails'),
                'Options' => ['Value' => val('mail', $form->getValue('KeyMap'), 'mail')]
            ],
            'KeyMap[photo]' => [
                'LabelCode' => 'Photo',
                'Description' => t('The Key in the XML payload to designate Photo.'),
                'Options' => ['Value' => val('photo', $form->getValue('KeyMap'), 'photo')]
            ],
            'KeyMap[uid]' => [
                'LabelCode' => 'Display Name',
                'Description' => t('The Key in the XML payload to designate Display Name.'),
                'Options' => ['Value' => val('uid', $form->getValue('KeyMap'), 'uid')]
            ],
            'KeyMap[cn]' => [
                'LabelCode' => 'Full Name',
                'Description' => t('The Key in the XML payload to designate Full Name.'),
                'Options' => ['Value' => val('cn', $form->getValue('KeyMap'), 'cn')]
            ],
            'AssociationSecret' => [
                'LabelCode' => 'IDP Certificate',
                'Options' => ['Multiline' => true, 'Class' => 'TextBox BigInput']
            ],
            'IdentifierFormat' => [
                'LabelCode' => 'Identifier Format',
                'Description' => sprintf(t('Something like "%s"'), SamlSSOPlugin::IdentifierFormat),
            ],
            'IsDefault' => [
                'Control' => 'CheckBox',
                'LabelCode' => 'Make this connection your default signin method.'
            ],
            'SpPrivateKey' => [
                'LabelCode' => 'SP Private Key',
                'Description' => 'If you want to sign your requests then you need this key.',
                'Options' => ['Multiline' => true, 'Class' => 'TextBox BigInput']
            ],
            'SpCertificate' => [
                'LabelCode' => 'SP Certificate',
                'Description' => 'This is the certificate that you will give to your IDP.',
                'Options' => ['Multiline' => true, 'Class' => 'TextBox BigInput']
            ],
            'SignoutWithSAML' => [
                'Control' => 'CheckBox',
                'LabelCode' => 'Only sign out with valid SAML logout requests.'
            ],
        ];

        if ($authenticationKey) {
            $formStructure['AuthenticationKey']['Options'] = [
                'Disabled' => true,
            ];
        }

        if ($form->authenticatedPostBack()) {
            $form->setFormValue('AuthenticationSchemeAlias', 'saml');

            if ($authenticationKey) {
                $form->setFormValue('AuthenticationKey', $authenticationKey);
            }

            // Make sure the keys are in the correct form.
            $secret = $form->getFormValue('AssociationSecret');
            $form->setFormValue('AssociationSecret', SamlSSOPlugin::untrimCert($secret));

            $key = $form->getFormValue('PrivateKey');
            $form->setFormValue('PrivateKey', SamlSSOPlugin::untrimCert($key, 'RSA PRIVATE KEY'));

            $key = $form->getFormValue('PublicKey');
            $form->setFormValue('PublicKey', SamlSSOPlugin::untrimCert($key, 'RSA PUBLIC KEY'));

            $form->validateRule('AuthenticationKey', 'ValidateRequired', sprintf(t('%s is required.'), $formStructure['AuthenticationKey']['LabelCode']));
            $form->validateRule('AuthenticationKey', 'regex:/^[a-zA-Z0-9]+$/', sprintf(t('%s must be composed of letters and digits only.'), $formStructure['AuthenticationKey']['LabelCode']));
            $form->validateRule('SignInUrl', 'ValidateRequired', sprintf(t('%s is required.'), $formStructure['SignInUrl']['LabelCode']));
            $form->validateRule('EntityID', 'ValidateRequired', sprintf(t('%s is required.'), $formStructure['EntityID']['LabelCode']));
            $form->validateRule('Name', 'ValidateRequired', sprintf(t('%s is required.'), $formStructure['Name']['LabelCode']));
            $form->validateRule('IdentifierFormat', 'ValidateRequired', sprintf(t('%s is required.'), $formStructure['IdentifierFormat']['LabelCode']));

            if ($form->save() !== false) {
                $this->informMessage(t('Saved'));

                if (!$authenticationKey) {
                    redirectTo('/samlsso/edit/'.$form->getFormValue('AuthenticationKey'));
                }
            }
        }

        $this->setData('FormStructure', $formStructure);
        $this->setData('Title', t('SAML Connection'));

        $this->render('addedit', '', 'plugins/samlsso');
    }

    /**
     * Delete endpoint.
     *
     * @param $authenticationKey SAML Authentication key
     */
    public function delete($authenticationKey) {
        $this->permission('Garden.Settings.Manage');

        if (!$authenticationKey) {
            return;
        }

        if ($this->Form->authenticatedPostBack()) {
            $model = new Gdn_AuthenticationProviderModel();
            $model->delete([
                'AuthenticationSchemeAlias' => 'saml',
                'AuthenticationKey' => $authenticationKey,
            ]);
        }

        $this->setRedirectTo('/settings/samlsso');
        $this->render('blank', 'utility', 'dashboard');
    }

    /**
     * Set the state of a particular SAML connection.
     *
     * @param string $authenticationKey SAML Authentication key
     * @param string $state
     * @throws Exception
     */
    public function state($authenticationKey, $state) {
        $this->permission('Garden.Settings.Manage');
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }

        if (!$authenticationKey || !in_array($state, ['active', 'disabled'])) {
            return;
        }

        $model = new Gdn_AuthenticationProviderModel();

        $updatedFields = [
            'Active' => $state === 'active' ? 1 : 0
        ];
        // Safeguard against stupid people
        if (!$updatedFields['Active']) {
            $updatedFields['IsDefault'] = 0;
        }

        $model->update(
            $updatedFields,
            [
                'AuthenticationSchemeAlias' => 'saml',
                'AuthenticationKey' => $authenticationKey,
            ]
        );

        if ($state === 'active' ? 1 : 0) {
            $state = 'on';
            $url = '/samlsso/state/'.$authenticationKey.'/disabled';
        } else {
            $state = 'off';
            $url = '/samlsso/state/'.$authenticationKey.'/active';
        }
        $newToggle = wrap(
            anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', $url, 'Hijack'),
            'span',
            ['class' => "toggle-wrap toggle-wrap-$state"]
        );

        $this->jsonTarget("#provider_$authenticationKey .toggle-container", $newToggle);

        $this->render('blank', 'utility', 'dashboard');
    }
}
