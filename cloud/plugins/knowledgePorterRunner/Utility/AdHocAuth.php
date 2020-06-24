<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\KnowledgePorterRunner\Utility;

use Garden\Web\RequestInterface;
use Vanilla\Contracts\ConfigurationInterface;

/**
 * AdHocAuth
 *
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 */
class AdHocAuth {
    /** @var RequestInterface */
    protected $request;

    /** @var string */
    protected $porterToken;

    /**
     * AdHocAuth constructor.
     *
     * @param ConfigurationInterface $config
     * @param RequestInterface $request
     */
    public function __construct(ConfigurationInterface $config, RequestInterface $request) {
        $this->porterToken = $config->get('Plugins.KnowledgePorterRunner.token', null);
        $this->request = $request;
    }

    /**
     * Validate a Token
     *
     * @return bool
     * @throws AdHocAuth401Exception For invalid Token.
     * @throws AdHocAuth403Exception For missing authorization information.
     */
    public function validateToken() {
        if ($this->porterToken === null) {
            return true;
        }

        $authHeader = $this->request->getHeader('Authorization') ?? '';
        if ($authHeader === '') {
            throw new AdHocAuth403Exception();
        }

        $parts = explode(' ', $authHeader);

        if (count($parts) === 2 && strtolower($parts[0]) === "bearer") {
            if (hash_equals($parts[1], $this->porterToken)) {
                return true;
            }
        }

        throw new AdHocAuth401Exception();
    }
}
