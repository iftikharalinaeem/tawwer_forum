<?php
require_once('Settings.php');
/**
 * Create a SAML authorization request.
 */
class OneLogin_Saml_LogoutResponse {
    const ID_PREFIX = 'VANILLA';

    public $inResponseTo;

    public $get = array();

    /**
     * A SamlResponse class provided to the constructor.
     * @var OneLogin_Saml_Settings
     */
    protected $_settings;

    /**
     * Construct the response object.
     *
     * @param OneLogin_Saml_Settings $settings
     *   A SamlResponse settings object containing the necessary
     *   x509 certicate to decode the XML.
     */
    public function __construct(OneLogin_Saml_Settings $settings, $in_response_to, $get) {
        $this->_settings = $settings;
        $this->inResponseTo = $in_response_to;
        $this->get = $get;
    }

    /**
     * Generate the request.
     *
     * @return string A fully qualified URL that can be redirected to in order to process the authorization request.
     */
    public function getRedirectUrl() {
        $id = $this->_generateUniqueID();
        $issueInstant = $this->_getTimestamp();

        $request = <<<AUTHNREQUEST
<samlp:LogoutResponse
    xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
    xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
    ID="$id"
    Version="2.0"
    IssueInstant="$issueInstant"
    Destination="{$this->_settings->idpSingleSignOutUrl}"
    InResponseTo="{$this->inResponseTo}">
    <saml:Issuer>{$this->_settings->spIssuer}</saml:Issuer>
    <samlp:Status><samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/></samlp:Status>
</samlp:LogoutResponse>
AUTHNREQUEST;

      $deflatedRequest = gzdeflate($request);
      $base64Request = base64_encode($deflatedRequest);
      $get = array('SAMLResponse' => $base64Request);

      foreach ($this->get as $k => $v) {
         if ($v) {
            $get[$k] = $v;
         }
      }

      try {
         $this->signRequest($get);
      } catch (Exception $ex) {

      }

      return $this->_settings->idpSingleSignOutUrl.
         (strpos($this->_settings->idpSingleSignOutUrl, '?') === false ? '?' : '&').
         http_build_query($get);
    }

    public function signRequest(&$get) {
       if (!$this->_settings->spPrivateKey)
          return;

       // Construct the string.
       $get['SigAlg'] = XMLSecurityKey::RSA_SHA1;
       $msg = http_build_query($get);
       $key = new XMLSecurityKey($get['SigAlg'], array('type' => 'private'));
       $key->loadKey($this->_settings->spPrivateKey, false, false);
       $get['Signature'] = base64_encode($key->signData($msg));
    }

    protected function _generateUniqueID()
    {
        return self::ID_PREFIX . sha1(uniqid(mt_rand(), TRUE));
    }

    protected function _getTimestamp()
    {
        $defaultTimezone = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $timestamp = strftime("%Y-%m-%dT%H:%M:%SZ");
        date_default_timezone_set($defaultTimezone);
        return $timestamp;
    }
}