<?php
class SAMLSSOController extends PluginController {

    public function add() {
        $this->edit();
    }

    public function edit($authenticationKey = null) {
        $this->Permission('Garden.Settings.Manage');

        $model = new Gdn_AuthenticationProviderModel();
        $form = new Gdn_Form();
        $form->setModel($model);
        $this->Form = $form;

        if ($form->authenticatedPostBack()) {
            $editedAuthenticationKey = $form->getFormValue('AuthenticationKey');

            $form->setFormValue('AuthenticationSchemeAlias', 'saml');

            // Make sure the keys are in the correct form.
            $secret = $form->getFormValue('AssociationSecret');
            $form->setFormValue('AssociationSecret', self::untrimCert($secret));

            $key = $form->getFormValue('PrivateKey');
            $form->setFormValue('PrivateKey', self::untrimCert($key, 'RSA PRIVATE KEY'));

            $key = $form->getFormValue('PublicKey');
            $form->setFormValue('PublicKey', self::untrimCert($key, 'RSA PUBLIC KEY'));

            if ($form->save() !== false) {
                $this->informMessage(t('Saved'));

                if ($editedAuthenticationKey != $authenticationKey) {
                    if ($authenticationKey !== null) {
                        $model->deleteID($authenticationKey);
                    }
                    redirect('/samlsso/edit/'.$editedAuthenticationKey);
                }
            }
        } elseif ($authenticationKey !== null) {
            $provider = Gdn_AuthenticationProviderModel::getProviderByKey($authenticationKey);
            $form->setData($provider);
            $this->setData('AuthenticationKey', $authenticationKey);
        }

        // Set up the form.
        $formStructure = [
            'AuthenticationKey' => [
                'LabelCode' => 'Connexion ID',
                'Description' => t('The connexion ID uniquely identifies this connection.'),
            ],
            'Name' => [
                'LabelCode' => 'Site Name',
                'Description' => t('Enter a short name for the site.', 'Enter a short name for the site. This is displayed on the signin buttons.'),
            ],
            'EntityID' => [
                'Description' => t('[???]'),
            ],
            'SignInUrl' => [
                'LabelCode' => 'Sign In URL',
                'Description' => t('The url that users use to sign in.').' '.t('Use {target} to specify a redirect.'),
            ],
            'RegisterUrl' => [
                'LabelCode' => 'Registration URL',
                'Description' => t('The url that users use to register for a new account.')
            ],
            'SignOutUrl' => [
                'LabelCode' => 'Sign Out URL',
                'Description' => t('The url that users use to sign out of your site.')
            ],
            'AssociationSecret' => [
                'LabelCode' => 'IDP Certificate',
                'Options' => ['Multiline' => true, 'Class' => 'TextBox BigInput']],
            'IdentifierFormat' => [
                'Description' => sprintf(t('Something like %s'), SamlSSOPlugin::IdentifierFormat),
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
            'Metadata' => [
                'Description' => 'Only sign out with valid SAML logout requests.'
            ],
        ];

        $this->setData('FormStructure', $formStructure);
        $this->setData('Title', t('SAML Connexion'));

        $this->render('addedit', '', 'plugins/samlsso');
    }


    /**
     *
     *
     * @param $cert
     * @return mixed|string
     */
    public static function trimCert($cert) {
        $cert = preg_replace('`-----[^-]*-----`i', '', $cert);
        $cert = trim(str_replace(["\r", "\n"], '', $cert));
        return $cert;
    }

    /**
     *
     *
     * @param $cert
     * @param string $type
     * @return string
     */
    public static function untrimCert($cert, $type = 'CERTIFICATE') {
        if (strpos($cert, '---BEGIN') === false) {
            // Convert the secret to a proper x509 certificate.
            $x509cert = trim(str_replace(["\r", "\n"], "", $cert));
            $x509cert = "-----BEGIN $type-----\n".chunk_split($x509cert, 64, "\n")."-----END $type-----\n";
            $cert = $x509cert;
        }
        return $cert;
    }
}
