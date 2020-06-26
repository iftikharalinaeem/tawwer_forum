<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\KnowledgePorterRunner\Utility;

use Gdn;
use Vanilla\Scheduler\Job\JobExecutionStatus;

/**
 * Class PorterRunnerMetaDao
 *
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 */
class PorterRunnerMetaDao {
    /** @var Gdn */
    protected $gdn;

    /**
     * PorterRunnerMetaDao constructor.
     *
     * @param Gdn $gdn
     */
    public function __construct(Gdn $gdn) {
        $this->gdn = $gdn;
    }

    /**
     * Get
     *
     * @param string $key
     * @return PorterRunnerMeta
     */
    public function get($key): PorterRunnerMeta {
        $values = json_decode(Gdn::get($key, null), true);
        if ($values === null) {
            return new PorterRunnerMeta($key);
        } else {
            return new PorterRunnerMeta(
                $key,
                $values['scheduled'],
                $values['finished'],
                JobExecutionStatus::looseStatus($values['status']),
                $values['jobId'],
                $values['done']
            );
        }
    }

    /**
     * Save
     *
     * @param PorterRunnerMeta $porterRunnerMeta
     * @return bool
     */
    public function save(PorterRunnerMeta $porterRunnerMeta): bool {
        $values = [
            'scheduled' => $porterRunnerMeta->getScheduled(),
            'finished' => $porterRunnerMeta->getFinished(),
            'status' => $porterRunnerMeta->getStatus()->getStatus(),
            'jobId' => $porterRunnerMeta->getJobId(),
            'done' => $porterRunnerMeta->isDone(),
        ];

        $this->gdn::set($porterRunnerMeta->getKey(), json_encode($values));

        return true;
    }
}
