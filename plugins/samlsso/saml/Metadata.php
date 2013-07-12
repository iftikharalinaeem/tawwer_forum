<?php

/**
 * Create SAML2 Metadata documents
 */
class OneLogin_Saml_Metadata
{
    /**
     * How long should the metadata be valid?
     */
    public $validSeconds;

    /**
     * Service settings
     * @var OneLogin_Saml_Settings
     */
    protected $_settings;

    /**
     * Create a new Metadata document
     * @param OneLogin_Saml_Settings $settings
     */
    public function __construct(OneLogin_Saml_Settings $settings)
    {
        $this->_settings = $settings;
        $this->validSeconds = strtotime('+1 week', 0);
    }

    /**
     * @return string
     */
    public function getXml()
    {
        $validUntil = $this->_getMetadataValidTimestamp();
        
        $signoutElem = '';
        if ($this->_settings->idpSingleSignOutUrl) {
           $signoutUrl = Url('/entry/signout', TRUE);
           
           $signoutElem = <<<EOT
<md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="{$signoutUrl}"/>
EOT;
        }
        
        $signingElem = '';
        $encryptionElem = '';
        
        if ($this->_settings->spCertificate) {
           $signingElem = $this->keyDescriptor($this->_settings->spCertificate, 'signing');
           $encryptionElem = $this->keyDescriptor($this->_settings->spCertificate, 'encryption');
        }

        return <<<METADATA_TEMPLATE
<?xml version="1.0"?>
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata"
                     validUntil="$validUntil"
                     entityID="{$this->_settings->spIssuer}">
    <md:SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
        $signingElem
        $encryptionElem
        <md:NameIDFormat>{$this->_settings->requestedNameIdFormat}</md:NameIDFormat>
        $signoutElem
        <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"
                                     Location="{$this->_settings->spReturnUrl}"
                                     index="1"/>
    </md:SPSSODescriptor>
</md:EntityDescriptor>
METADATA_TEMPLATE;
    }
    
    protected static function keyDescriptor($cert, $use = 'signing') {
       $x509 = openssl_x509_read($cert);
       openssl_x509_export($x509, $str_cert);
       $str_cert = SamlSSOPlugin::TrimCert($str_cert);
       
       $result = <<<EOT
<md:KeyDescriptor use="$use">
   <ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
      <ds:X509Data>
         <ds:X509Certificate>$str_cert</ds:X509Certificate>
      </ds:X509Data>
   </ds:KeyInfo>
</md:KeyDescriptor>
EOT;
       return $result;
    }

    protected function _getMetadataValidTimestamp()
    {
        $timeZone = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $time = strftime("%Y-%m-%dT%H:%M:%SZ", time() + $this->validSeconds);
        date_default_timezone_set($timeZone);
        return $time;
    }
}