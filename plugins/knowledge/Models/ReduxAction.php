<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

use Garden\Web\Data;

/**
 * Class ReduxAction.
 */
class ReduxAction implements \JsonSerializable {
    /**
     * @var string $type Redux action type
     */
    protected $type;
    /**
     * @var array $payload Redux action payload
     */
    protected $payload;

    /**
     * Create an redux action
     *
     * @param string $type Redux action type to create
     * @param Data $data Redux payload data.
     * @param array $requestParams The params if this is a FSA. This opts in to the new action structure (FSA).
     */
    public function __construct(string $type, Data $data, array $requestParams = null) {
        $this->type = $type;
        $this->payload = $requestParams !== null ? ['result' => $data, 'params' => $requestParams] : ['data' => $data];
    }

    /**
     * Get the array for JSON serialization.
     */
    public function jsonSerialize(): array {
        return $this->value();
    }


    /**
     * Return an array of redux action to be sent.
     *
     * @return array
     */
    public function value(): array {
        return [
            "type" => $this->type,
            "payload" => $this->payload,
        ];
    }
}
