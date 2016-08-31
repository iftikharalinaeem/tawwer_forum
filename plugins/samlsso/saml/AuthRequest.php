<?php
require_once('Settings.php');

/**
 * Create a SAML authorization request.
 */
class OneLogin_Saml_AuthRequest {
    const ID_PREFIX = 'vf-';

    /**
     * A SamlResponse class provided to the constructor.
     * @var OneLogin_Saml_Settings
     */
    protected $_settings;

    /**
     * Whether or not this is a passive request.
     * @var bool
     */
    public $isPassive = true;

    /**
     * @var string The last request ID that was generated.
     */
    public $lastID;

    /**
     * @var string A
     */
    public $relayState = '';

    /**
     * Construct the response object.
     *
     * @param OneLogin_Saml_Settings $settings
     *   A SamlResponse settings object containing the necessary
     *   x509 certicate to decode the XML.
     */
    public function __construct(OneLogin_Saml_Settings $settings) {
        $this->_settings = $settings;
    }

    /**
     * Generate the request.
     *
     * @return string A fully qualified URL that can be redirected to in order to process the authorization request.
     */
    public function getRedirectUrl() {
        $id = $this->lastID = $this->generateUniqueID();
        $issueInstant = $this->getTimestamp();
        $isPassive = $this->isPassive ? 'true' : 'false';
        $Destination = htmlspecialchars($this->_settings->idpSingleSignOnUrl);

        $request = <<<AUTHNREQUEST
<samlp:AuthnRequest
    xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
    xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
    ID="$id"
    Version="2.0"
    IssueInstant="$issueInstant"
    Destination="$Destination"
    IsPassive="$isPassive"
    ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"
    AssertionConsumerServiceURL="{$this->_settings->spReturnUrl}">
    <saml:Issuer>{$this->_settings->spIssuer}</saml:Issuer>
    <samlp:NameIDPolicy
        Format="{$this->_settings->requestedNameIdFormat}"
        AllowCreate="true"></samlp:NameIDPolicy>
    <samlp:RequestedAuthnContext Comparison="exact">
        <saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport</saml:AuthnContextClassRef>
    </samlp:RequestedAuthnContext>
</samlp:AuthnRequest>
AUTHNREQUEST;

        $deflatedRequest = gzdeflate($request);
        $base64Request = base64_encode($deflatedRequest);
        $get = ['SAMLRequest' => $base64Request];

        if ($this->relayState) {
            $get['RelayState'] = $this->relayState;
        }

        try {
            $this->signRequest($get);
        } catch (Exception $ex) {
            // do nothing.
        }

        return $this->_settings->idpSingleSignOnUrl .
        (strpos($this->_settings->idpSingleSignOnUrl, '?') === false ? '?' : '&') .
        http_build_query($get);
    }

    public function signRequest(&$get) {
        if (!$this->_settings->spPrivateKey) {
            return;
        }

        // Construct the string.
        $get['SigAlg'] = XMLSecurityKey::RSA_SHA1;
        $msg = http_build_query($get);
        $key = new XMLSecurityKey($get['SigAlg'], ['type' => 'private']);
        $key->loadKey($this->_settings->spPrivateKey, false, false);
        $get['Signature'] = base64_encode($key->signData($msg));
    }

    protected function generateUniqueID() {
        return self::ID_PREFIX . sha1(uniqid(mt_rand(), true));
    }

    protected function getTimestamp() {
        $defaultTimezone = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $timestamp = strftime("%Y-%m-%dT%H:%M:%SZ");
        date_default_timezone_set($defaultTimezone);
        return $timestamp;
    }
}
