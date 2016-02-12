<?php

/**
 * Determine if the SAML response is valid using a provided x509 certificate.
 */
class OneLogin_Saml_XmlSec
{
    /**
     * A SamlResponse class provided to the constructor.
     * @var OneLogin_Saml_Settings
     */
    protected $_settings;

    /**
     * The document to be tested.
     * @var DomDocument
     */
    protected $_document;

    /**
     * Construct the SamlXmlSec object.
     *
     * @param OneLogin_Saml_Settings $settings A SamlResponse settings object containing the necessary
     *                                          x509 certicate to test the document.
     * @param OneLogin_Saml_Response $response The document to test.
     */
    public function __construct(OneLogin_Saml_Settings $settings, OneLogin_Saml_Response $response)
    {
        $this->_settings = $settings;
        $this->_document = clone $response->document;
    }

    /**
     * @return DomDocument Returns the document that was being checked.
     */
    public function getDocument() {
        return $this->_document;
    }

    /**
     * Verify that the document only contains a single Assertion
     *
     * @return bool TRUE if the document passes.
     */
    public function validateNumAssertions()
    {
        $rootNode = $this->_document;
        $assertionNodes = $rootNode->getElementsByTagName('Assertion');
        return ($assertionNodes->length < 2);
    }

    /**
     * Verify that the document contains a successful status.
     *
     * If the document doesn't contain any status then it's also considered valid for the purposes of this check. The
     * reason for this is another validation should fail in this case and its error message should be seen.
     *
     * @param bool $throw Whether or not to throw an exception if the validation fails.
     * @return bool Returns **true** if the stats is successful or **false** otherwise.
     */
    public function validateStatus($throw = false) {
        $rootNode = $this->getDocument();

        $errors = [];
        $statusNodes = $rootNode->getElementsByTagName('StatusCode');
        for ($i = 0; $i < $statusNodes->length; $i++) {
            $node = $statusNodes->item($i);

            $value = $node->attributes->getNamedItem('Value');
            $value = $value ? $value->textContent : '';

            if (!$value || stripos($value, 'success') !== false) {
                return true;
            }

            $error = t('saml.error.'.$value, '');
            if (!$error) {
                // There is no translation for this error.
                if (empty($errors)) {
                    $error = sprintf(t('The IDP returned the following error: "%s".'), $value);
                } else {
                    $error = sprintf(t('The IDP also returned the following error: "%s".'), $value);
                }
            }
            $errors[$value] = $error;
        }

        if (empty($errors)) {
            return true;
        }

        // Check to see if there is a dedicated error message from the response.
        $statusMessage = $rootNode->getElementsByTagName('StatusMessage');
        if ($statusMessage->length > 0) {
            $errors = [];
            for ($i = 0; $i < $statusMessage->length; $i++) {
                $node = $statusMessage->item($i);
                $errors[] = $node->textContent;
            }
        }

        if ($throw) {
            throw new Gdn_UserException(implode(' ', $errors), 400);
        } else {
            return false;
        }
    }

    /**
     * Verify that the document is still valid according
     *
     * @return bool
     */
    public function validateTimestamps()
    {
        $rootNode = $this->_document;
        $timestampNodes = $rootNode->getElementsByTagName('Conditions');
        for ($i = 0; $i < $timestampNodes->length; $i++) {
            $nbAttribute = $timestampNodes->item($i)->attributes->getNamedItem("NotBefore");
            $naAttribute = $timestampNodes->item($i)->attributes->getNamedItem("NotOnOrAfter");
            if ($nbAttribute && strtotime($nbAttribute->textContent) > time()) {
                return false;
            }
            if ($naAttribute && strtotime($naAttribute->textContent) <= time()) {
                return false;
            }
        }
        return true;
    }

    public function decrypt() {
        $enc = new XMLSecEnc();
        $encrypted = $enc->locateEncryptedData($this->_document);

        if (!$encrypted) {
            return;
        }
        $enc->setNode($encrypted);
        $enc->type = $encrypted->getAttribute('Type');


        $key = $enc->locateKey($encrypted);
        if (!$key) {
            throw new Exception('There was no key for the encrypted data.', 400);
        }
        $encKey = XMLSecEnc::staticLocateKeyInfo($key, $encrypted);
        if ($encKey) {
            $encKey->loadKey($this->_settings->spPrivateKey, false, false);
            $decKey = $encKey->encryptedCtx->decryptKey($encKey);
        }

        $key->loadKey($decKey, FALSE, FALSE);
        $enc->decryptNode($key);
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isValid()
    {
        // First decrypt the doc.
        $this->decrypt();

        $xml = $this->_document->C14N();
//        echo $xml;
//        die();

        $objXMLSecDSig = new XMLSecurityDSig();

        $objDSig = $objXMLSecDSig->locateSignature($this->_document);
        if (!$objDSig) {
            throw new Exception('Cannot locate Signature Node');
        }
        $objXMLSecDSig->canonicalizeSignedInfo();
        $objXMLSecDSig->idKeys = array('ID');

        $retVal = $objXMLSecDSig->validateReference();
        if (!$retVal) {
            throw new Exception('Reference Validation Failed');
        }

        $this->validateStatus(true);

        $singleAssertion = $this->validateNumAssertions();
        if (!$singleAssertion) {
            throw new Exception('Multiple assertions are not supported');
        }

        $validTimestamps = $this->validateTimestamps();
        if (!$validTimestamps) {
            throw new Exception('Timing issues (please check your clock settings)
            ');
        }

        $objKey = $objXMLSecDSig->locateKey();
        if (!$objKey) {
            throw new Exception('We have no idea about the key');
        }

        XMLSecEnc::staticLocateKeyInfo($objKey, $objDSig);

        $objKey->loadKey($this->_settings->idpPublicCertificate, FALSE, TRUE);

        return ($objXMLSecDSig->verify($objKey) === 1);
    }
}