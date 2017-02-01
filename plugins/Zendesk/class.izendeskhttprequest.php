<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

/**
 * Interface IZendeskHttpRequest
 */
interface IZendeskHttpRequest {
    /**
     * Set Option.
     *
     * @param string $name Name.
     * @param string $value Value.
     */
    public function setOption($name, $value);

    /**
     * Execute Request.
     */
    public function execute();

    /**
     * GetInfo.
     *
     * @param string $name Name.
     */
    public function getInfo($name);

    /**
     * Close.
     */
    public function close();
}
