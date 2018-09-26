<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

/**
 * Class ReduxAction.
 */
abstract class ReduxAction {
    const SUCCESS = 'SUCCESS';

    /**
     * @var string $type Redux action type
     */
    protected $type;

    /**
     * @var array $payload Redux action payload
     */
    protected $payload;

    /**
     * Create an redux action.
     *
     * @param array $data Redux payload data
     */
    public function __construct(array $data) {
        $this->type = self::SUCCESS;
        $this->payload = $data;
    }

    /**
     * Return an array of redux action to be sent.
     *
     * @return array
     */
    public function getReduxAction() : array {
        return [
            "type" => $this->type,
            "payload" => [
                "data" => $this->payload,
            ],
        ];
    }
}
