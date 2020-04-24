<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\KnowledgePorterRunner\Utility;

use Garden\Web\Exception\ClientException;

/**
 * Class AdHocAuth401Exception.
 *
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 */
class AdHocAuth401Exception extends ClientException {

    /**
     * AdHocAuth401Exception constructor.
     *
     * @param string $message
     * @param int $code
     * @param array $context
     */
    public function __construct(string $message = 'Invalid Token', int $code = 401, array $context = ['description' => 'Invalid Token']) {
        parent::__construct($message, $code, $context);
    }
}
