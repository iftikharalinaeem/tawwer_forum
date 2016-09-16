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
     * @return The SessionNotOnOrAfter as unix epoc or null if not present
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

        $attributes = [];
        /* @var SimpleXMLElement $entry */
        foreach ($entries as $entry) {
            $friendlyName = (string)$entry['FriendlyName'];
            $attributeName = (string)$entry['Name'];

            // We have to do another xpath to get our desired child nodes here.
            $entry->registerXPathNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');
            $attributeValues = [];

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

        $xml = simplexml_load_string($this->document->saveXML());
        $xml->registerXPathNamespace('samlp', 'urn:oasis:names:tc:SAML:2.0:protocol');
        $xml->registerXPathNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');
        $xml->registerXPathNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

        // Some SAML responses put the Signature in the Assertion, some don't. Loop through the possible paths.
        $signatureQueries = [
            '/samlp:Response//saml:Assertion/ds:Signature/ds:SignedInfo/ds:Reference',
            '/samlp:Response//ds:Signature/ds:SignedInfo/ds:Reference'
        ];

        foreach ($signatureQueries as $signatureQuery) {
            /* @var SimpleXMLElement $refNode */
            $refNode = reset($xml->xpath($signatureQuery));
            if ($refNode) {
                break;
            }
        }
        if (!$refNode) {
            // if no signature is found, dump the structure to the Logger, throw an error message.
            $xmlstr = $this->document->saveXML();
            Logger::event('saml_response', Logger::ERROR, 'SAML Signature not found', (array) $xmlstr);
            throw new Exception('Unable to query assertion, no Signature Reference found?', 422);
        }

        $id = substr((string)$refNode['URI'], 1);

        // Again, to accommodate SAML documents that put the ID on the Assertion
        // AND documents that nest the Assertion somewhere in a node with the ID
        // we loop through the possible places until we find an assertion.
        $nameQueries = [
            "/samlp:Response//saml:Assertion[@ID='$id']".$assertionXpath,
            "//*[@ID='$id']//saml:Assertion".$assertionXpath
        ];
        foreach ($nameQueries as $nameQuery) {
            $assertion = $xml->xpath($nameQuery);
            if ($assertion) {
                break;
            }
        }
        if (!$assertion) {
            if ($assertionXpath === '/saml:Subject/saml:NameID') {
                // This means no NameID has been found, dump the structure to the Logger, throw an error message.
                $xmlstr = $this->document->saveXML();
                // Protip: if you want to read this output, you have to convert the \" to ' and use an XML formatter in Sublime.
                Logger::event('saml_response', Logger::ERROR, 'SAML NameID Not Found.', (array) $xmlstr);
                throw new Exception('Unable to find the unique identifier sent by the identity provider.', 422);
            } else {
                // if no assertion is found, dump the structure to the Logger, don't throw an error message.
                Logger::event('saml_response', Logger::INFO, "SAML Missing Assertion {$assertionXpath}.", []);
            }
        }

        Logger::event('saml_response', Logger::INFO, "Assertion Found {$nameQuery}.", (array)$assertion);
        return $assertion;
    }
}
