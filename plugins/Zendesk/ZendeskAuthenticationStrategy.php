<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

/**
 * Interface AuthenticationStrategy
 */
interface ZendeskAuthenticationStrategy {

    /**
     * Define authorization method.
     *
     * @return string
     */
    public function getAuthentication(): string;
}
