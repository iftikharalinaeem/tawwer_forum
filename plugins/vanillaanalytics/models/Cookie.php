<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 * @package vanillaanalytics
 */
namespace Vanilla\Analytics;

use Gdn_Session;

/**
 * A class for managing analytics cookie information.
 */
class Cookie {

    /** Target lifespan of the cookie. Must be a strtotime-compatible string. */
    const EXPIRY = '+2 years';

    /** Bit mask to determine if a privacy flag is opt-in. */
    const PRIVACY_MASK_OPT_IN = 1;

    /** Bit mask to determine if a privacy flag is opt-out. */
    const PRIVACY_MASK_OPT_OUT = 0;

    /** Bit mask to determine if a privacy flag was automatically set for a user. */
    const PRIVACY_MASK_AUTO = 2;

    /** @var bool */
    private $modified;

    /** @var int */
    private $privacy;

    /** @var Gdn_Session */
    private $session;

    /** @var string */
    private $secondarySessionID;

    /** @var string */
    private $sessionID;

    /** @var string */
    private $UUID;

    /**
     * Cookie constructor.
     *
     * @param Gdn_Session $session
     */
    public function __construct(Gdn_Session $session) {
        $this->session = $session;
    }

    /**
     * Get privacy flag.
     *
     * @return int
     */
    public function getPrivacy() {
        return $this->privacy;
    }

    /**
     * Get the secondary session ID.
     *
     * @return string
     */
    public function getSecondarySessionID() {
        return $this->secondarySessionID;
    }

    /**
     * Get session ID.
     *
     * @return string
     */
    public function getSessionID() {
        return $this->sessionID;
    }

    /**
     * Get universally unique identifier (UUID).
     *
     * @return string
     */
    public function getUUID() {
        return $this->UUID;
    }

    /**
     * Has data been modified since the last load or save operation?
     *
     * @return bool
     */
    public function isModified(): bool {
        return $this->modified;
    }

    /**
     * Does the privacy flag indicate it was automatically set?
     *
     * @return bool
     */
    public function isPrivacyAuto(): bool {
        $result = (bool)($this->privacy & self::PRIVACY_MASK_AUTO);
        return $result;
    }

    /**
     * Does the privacy flag indicate the user is opting into tracking?
     *
     * @return bool
     */
    public function isPrivacyOptIn(): bool {
        $result = (bool)($this->privacy & self::PRIVACY_MASK_OPT_IN);
        return $result;
    }

    /**
     * Load state from an array.
     *
     * @param array $data
     * @return self
     */
    public function loadArray(array $data) {
        $privacy = $data['pv'] ?? null;
        $secondarySessionID = $data['secondarySessionID'] ?? null;
        $sessionID = $data['sessionID'] ?? null;
        $UUID = $data['uuid'] ?? null;

        $result = $this
            ->setPrivacy($privacy)
            ->setSecondarySessionID($secondarySessionID)
            ->setSessionID($sessionID)
            ->setUUID($UUID)
        ;
        $this->modified = false;
        return $result;
    }

    /**
     * Load state from the analytics cookie.
     *
     * @param string $cookieSuffix
     * @return self
     */
    public function loadCookie(string $cookieSuffix) {
        $cookie = $this->session->getCookie($cookieSuffix, '');
        $result = $this->loadJSON($cookie);
        return $result;
    }

    /**
     * Load state from a JSON string.
     *
     * @param string $json
     * @return self
     */
    public function loadJSON(string $json) {
        $data = json_decode($json, true);
        $data = is_array($data) ? $data : [];
        $result = $this->loadArray($data);
        return $result;
    }

    /**
     * Save state to browser cookies. By default, cookie updates will only be sent if modifications were detected.
     *
     * @param string $suffix Target cookie suffix.
     * @param bool $force Force a write to browser cookies, even if modifications haven't been detected.
     */
    public function saveToCookie(string $suffix, bool $force = false) {
        $cookie = [];

        if (($privacy = $this->getPrivacy()) !== null) {
            $cookie['pv'] = $privacy;
        }
        if ($secondarySessionID = $this->getSecondarySessionID()) {
            $cookie['secondarySessionID'] = $secondarySessionID;
        }
        if ($sessionID = $this->getSessionID()) {
            $cookie['sessionID'] = $sessionID;
        }
        if ($UUID = $this->getUUID()) {
            $cookie['uuid'] = $UUID;
        }

        if ($this->isModified() || $force) {
            $this->session->setCookie(
                $suffix,
                json_encode($cookie),
                strtotime(self::EXPIRY)
            );
        }
        $this->modified = false;
    }

    /**
     * Set privacy flag.
     *
     * @param int|null $privacy
     * @return self
     */
    public function setPrivacy($privacy) {
        if (($privacy = filter_var($privacy, FILTER_VALIDATE_INT)) !== false || $privacy === null) {
            $this->writeProperty('privacy', $privacy);
        }
        return $this;
    }

    /**
     * Set the secondary session ID.
     *
     * @param string|null $sessionID
     * @return self
     */
    public function setSecondarySessionID($sessionID) {
        if (is_string($sessionID) || $sessionID === null) {
            $this->writeProperty('secondarySessionID', $sessionID);
        }
        return $this;
    }

    /**
     * Set session ID.
     *
     * @param string|null $sessionID
     * @return self
     */
    public function setSessionID($sessionID) {
        if (is_string($sessionID) || $sessionID === null) {
            $this->writeProperty('sessionID', $sessionID);
        }
        return $this;
    }

    /**
     * Set universally unique identifier (UUID).
     *
     * @param string|null $UUID
     * @return self
     */
    public function setUUID($UUID) {
        if (is_string($UUID) || $UUID === null) {
            $this->writeProperty('UUID', $UUID);
        }
        return $this;
    }

    /**
     * Save an object property value and update the modified flag on the instance.
     *
     * @param string $property
     * @param mixed $value
     */
    private function writeProperty(string $property, $value) {
        $this->$property = $value;
        $this->modified = true;
    }
}
