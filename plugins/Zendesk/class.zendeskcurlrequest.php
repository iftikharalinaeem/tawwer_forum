<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

/**
 * Class ZendeskCurlRequest.
 */
class ZendeskCurlRequest implements IZendeskHttpRequest {
    private $handle = null;

    /**
     * Init Curl.
     *
     * @param string $url Url to zendesk.
     */
    public function __construct($url = '') {
        $this->handle = curl_init($url);
    }

    /**
     * Set Curl Option.
     *
     * @param string $name Option Name.
     * @param string $value Option Value.
     *
     * @return bool
     */
    public function setOption($name, $value) {
        return curl_setopt($this->handle, $name, $value);
    }

    /**
     * Execute curl request.
     *
     * @return mixed
     */
    public function execute() {
        return curl_exec($this->handle);
    }

    /**
     * Get curl Info.
     *
     * @param string $name Name.
     *
     * @return mixed
     */
    public function getInfo($name) {
        return curl_getinfo($this->handle, $name);
    }

    /**
     * Close curl.
     */
    public function close() {
        curl_close($this->handle);
    }

    /**
     * Ensures curl is closed.
     */
    public function __destruct() {
        $this->close();
    }
}
