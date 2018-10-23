<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
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
     * @param array $data Redux payload data
     */
    public function __construct(string $type, Data $data) {
        $this->type = $type;
        $this->payload = $data;
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
            "payload" => [
                "data" => $this->payload,
            ],
        ];
    }
}
