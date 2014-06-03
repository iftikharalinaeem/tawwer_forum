<?php

/**
 * Parse the SAML response and maintain the XML for it.
 */
class OneLogin_Saml_Response
{
    /**
     * @var OneLogin_Saml_Settings
     */
    protected $_settings;

    /**
     * The decoded, unprocessed XML assertion provided to the constructor.
     * @var string
     */
    public $assertion;

    /**
     * A DOMDocument class loaded from the $assertion.
     * @var DomDocument
     */
    public $document;

    /**
     * Construct the response object.
     *
     * @param OneLogin_Saml_Settings $settings Settings containing the necessary X.509 certificate to decode the XML.
     * @param string $assertion A UUEncoded SAML assertion from the IdP.
     */
    public function __construct(OneLogin_Saml_Settings $settings, $assertion)
    {
        $this->_settings = $settings;
        $this->assertion = base64_decode($assertion);
        $this->document = new DOMDocument();
        $this->document->loadXML($this->assertion);
    }

    public function decrypt() {
        $xmlSec = new OneLogin_Saml_XmlSec($this->_settings, $this);
        $xmlSec->decrypt();
        $this->document = $xmlSec->getDocument();
    }

    /**
     * Determine if the SAML Response is valid using the certificate.
     *
     * @throws Exception
     * @return bool Validate the document
     */
    public function isValid() {
        $this->decrypt();
        $xmlSec = new OneLogin_Saml_XmlSec($this->_settings, $this);

        return $xmlSec->isValid();
    }

    /**
     * Get the NameID provided by the SAML response from the IdP.
     */
    public function getNameId() {
        $entries = $this->queryAssertion('/saml:Subject/saml:NameID');
        return (string)reset($entries);
    }

    /**
     * Get the SessionNotOnOrAfter attribute, as Unix Epoc, from the
     * AuthnStatement element.
     * Using this attribute, the IdP suggests the local session expiration
     * time.
     * 
     * @return The SessionNotOnOrAfter as unix epoc or NULL if not present
     */
    public function getSessionNotOnOrAfter() {
        $entries = $this->queryAssertion('/saml:AuthnStatement[@SessionNotOnOrAfter]');
        if (count($entries) == 0) {
            return null;
        }
        $notOnOrAfter = (string)$entries[0]['SessionNotOnOrAfter'];
        return strtotime($notOnOrAfter);
    }

    /**
     * Get the saml attributes from a response.
     *
     * @return array Returns an array of attributes indexed by the friendly name and the full name.
     */
    public function getAttributes() {
        $entries = $this->queryAssertion('/saml:AttributeStatement/saml:Attribute');

        $attributes = array();
        /* @var SimpleXMLElement $entry */
        foreach ($entries as $entry) {
            $friendlyName = (string)$entry['FriendlyName'];
            $attributeName = (string)$entry['Name'];

            // We have to do another xpath to get our desired child nodes here.
            $entry->registerXPathNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');
            $attributeValues = array();

            /* @var SimpleXMLElement $childNode */
            foreach ($entry->xpath('saml:AttributeValue') as $childNode) {
                $attributeValues[] = (string)$childNode;
            }

            $attributes[$attributeName] = $attributeValues;
            if ($friendlyName) {
                $attributes[$friendlyName] = $attributeValues;
            }
        }
        return $attributes;
    }

    /**
     * Query a set of nodes under the saml:Assertion part of the response.
     *
     * @param string $assertionXpath
     * @return SimpleXMLElement[]
     * @throws Exception Throws a 422 exception when there is no signature reference.
     */
    protected function queryAssertion($assertionXpath) {
        // The DOMDocument doesn't xpath namespaces properly if they aren't on the root element.
        // Use SimpleXML instead.
        $xmlstr = $this->document->saveXML();

        $xml = simplexml_load_string($this->document->saveXML());
        $xml->registerXPathNamespace('samlp', 'urn:oasis:names:tc:SAML:2.0:protocol');
        $xml->registerXPathNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');
        $xml->registerXPathNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

        $signatureQuery = '/samlp:Response//saml:Assertion/ds:Signature/ds:SignedInfo/ds:Reference';

        /* @var SimpleXMLElement $refNode */
        $refNode = reset($xml->xpath($signatureQuery));
        if (!$refNode) {
            throw new Exception('Unable to query assertion, no Signature Reference found?', 422);
        }

        $id = substr((string)$refNode['URI'], 1);

        $nameQuery = "/samlp:Response//saml:Assertion[@ID='$id']".$assertionXpath;
        return $xml->xpath($nameQuery);
    }
}
