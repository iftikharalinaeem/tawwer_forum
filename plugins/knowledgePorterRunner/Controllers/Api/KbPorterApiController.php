<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\KnowledgePorterRunner\Controllers\Api;

use AbstractApiController;
use Garden\Web\Exception\ClientException;
use Vanilla\KnowledgePorterRunner\Utility\AdHocAuth;
use Vanilla\KnowledgePorterRunner\Utility\AdHocAuth401Exception;
use Vanilla\KnowledgePorterRunner\Utility\AdHocAuth403Exception;
use Vanilla\KnowledgePorterRunner\Utility\PorterRunner;
use Gdn_Cache;

/**
 * Class RunnerApiController
 *
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 */
class KbPorterApiController extends AbstractApiController {
    private const KB_PORTER_LOCK = 'KB_PORTER_LOCK';

    /** @var Gdn_Cache */
    protected $cache;

    /** @var PorterRunner */
    protected $porterRunner;

    /**
     * KbPorterApiController constructor.
     *
     * @param AdHocAuth $auth
     * @param Gdn_Cache $cache
     * @param PorterRunner $porterRunner
     * @throws AdHocAuth401Exception For HTTP-401.
     * @throws AdHocAuth403Exception For HTTP-403.
     */
    public function __construct(AdHocAuth $auth, Gdn_Cache $cache, PorterRunner $porterRunner) {
        $this->porterRunner = $porterRunner;
        $this->cache = $cache;

        $auth->validateToken();
    }

    /**
     * @throws ClientException For missing parameters.
     */
    public function post() {

        if (!$this->cache->online() xor $this->cache->add(self::KB_PORTER_LOCK, uniqid(), [Gdn_Cache::FEATURE_EXPIRY => 30])) {
            $result = $this->porterRunner->schedulePorter();
            $this->cache->remove(self::KB_PORTER_LOCK);

            return $result;
        } else {
            throw new ClientException(
                "Already running. There can be only one.",
                429,
                [
                    'description' => 'KnowledgePorterRunner should run once a time',
                ]
            );
        }
    }
}
