<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\KnowledgePorterRunner\Utility;

use Garden\Web\Exception\ClientException;

/**
 * Class AdHocAuth403Exception.
 *
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 */
class AdHocAuth403Exception extends ClientException {

    /**
     * AdHocAuth403Exception constructor.
     *
     * @param string $message
     * @param int $code
     * @param array $context
     */
    public function __construct(string $message = 'Forbidden Error', int $code = 403, array $context = ['description' => 'Missing Token']) {
        parent::__construct($message, $code, $context);
    }
}
