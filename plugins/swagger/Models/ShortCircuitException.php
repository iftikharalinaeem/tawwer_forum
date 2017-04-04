<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Swagger\Models;

use Exception;

/**
 * For internal use.
 */
class ShortCircuitException extends \Exception {
    public function __construct() {
        parent::__construct('Short Circuit', 500);
    }
}
