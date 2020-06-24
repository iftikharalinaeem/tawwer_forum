<?php
require_once('Settings.php');
/**
 * Create a SAML authorization request.
 */
class OneLogin_Saml_LogoutRequest
{
    const ID_PREFIX = 'VANILLA';

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
    public function __construct(OneLogin_Saml_Settings $settings)
    {
        $this->_settings = $settings;
    }

    /**
     * Generate the request.
     *
     * @return string A fully qualified URL that can be redirected to in order to process the authorization request.
     */
    public function getRedirectUrl($nameID)
    {
        $id = $this->generateUniqueID();
        $issueInstant = $this->getTimestamp();

        $request = <<<AUTHNREQUEST
<samlp:LogoutRequest
    xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
    xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
    ID="$id"
    Version="2.0"
    IssueInstant="$issueInstant"
    Destination="{$this->_settings->spSignoutReturnUrl}">
    <saml:Issuer>{$this->_settings->spIssuer}</saml:Issuer>
    <saml:NameID
        Format="{$this->_settings->requestedNameIdFormat}">{$nameID}</saml:NameID>
</samlp:LogoutRequest>
AUTHNREQUEST;

      $deflatedRequest = gzdeflate($request);
      $base64Request = base64_encode($deflatedRequest);
      $get = ['SAMLRequest' => $base64Request];

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
       $key = new XMLSecurityKey($get['SigAlg'], ['type' => 'private']);
       $key->loadKey($this->_settings->spPrivateKey, false, false);
       $get['Signature'] = base64_encode($key->signData($msg));
    }

    protected function generateUniqueID()
    {
        return self::ID_PREFIX.sha1(uniqid(mt_rand(), true));
    }

    protected function getTimestamp()
    {
        $defaultTimezone = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $timestamp = strftime("%Y-%m-%dT%H:%M:%SZ");
        date_default_timezone_set($defaultTimezone);
        return $timestamp;
    }
}
