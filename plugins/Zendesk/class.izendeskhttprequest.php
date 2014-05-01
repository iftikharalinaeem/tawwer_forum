<?php
/**
 * @copyright 2014 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Interface IZendeskHttpRequest
 */
interface IZendeskHttpRequest
{
    /**
     * @param string $name
     * @param string $value
     */
    public function setOption($name, $value);

    /**
     * execute
     */
    public function execute();

    /**
     * GetInfo
     * @param string $name
     */
    public function getInfo($name);

    /**
     * close
     */
    public function close();
}
