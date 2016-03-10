<?php

/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 */
class EmailLogModel extends Gdn_Model {

    /**
     * @var string The amount of time to delete logs after.
     */
    protected $pruneAfter;

    /**
     * EmailLogModel constructor.
     */
    public function __construct() {
        parent::__construct('EmailLog');
        $this->setPruneAfter('-90 days');
    }

    /**
     * Get the delete after time.
     *
     * @return string Returns a string compatible with {@link strtotime()}.
     */
    public function getPruneAfter() {
        return $this->pruneAfter;
    }

    /**
     * Get the exact timestamp to prune.
     *
     * @return int Returns a timestamp or zero if no pruning should take place.
     */
    public function getPruneAfterTimestamp() {
        if (!$this->pruneAfter) {
            return 0;
        } else {
            return strtotime($this->pruneAfter);
        }
    }

    /**
     * Set the deleteAfter.
     *
     * @param string $pruneAfter A string compatible with {@link strtotime()}. Be sure to specify a negative string.
     * @return DbLogger Returns `$this` for fluent calls.
     */
    public function setPruneAfter($pruneAfter) {
        if ($pruneAfter) {
            // Make sure the string is negative.
            $now = time();
            $testTime = strtotime($pruneAfter, $now);
            if ($testTime === false) {
                throw new InvalidArgumentException('Invalid timespan value for "prune after".', 400);
            }
            if ($testTime >= $now) {
                throw new InvalidArgumentException('You must specify a timespan in the past for "prune after".', 400);
            }
        }

        $this->pruneAfter = $pruneAfter;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function insert($Fields) {
        $this->serializeFields($Fields);

        $r = parent::insert($Fields);

        // Delete some old logs.
        if ($timestamp = $this->getPruneAfterTimestamp()) {
            $px = Gdn::sql()->Database->DatabasePrefix;
            $sql = "delete from {$px}EmailLog where DateInserted <= :date limit 10";
            $rd = Gdn::database()->query($sql, ['date' => Gdn_Format::toDateTime($timestamp)]);
        }

        return $r;
    }

    /**
     * Serialize array fields before inserting into the database.
     *
     * @param array &$fields The fields to serialize.
     */
    private function serializeFields(&$fields) {
        if (isset($fields['Post']) && is_array($fields['Post'])) {
            $fields['Post'] = json_encode($fields['Post'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setField($RowID, $Property, $Value = false) {
        if (!is_array($Property)) {
            $array = [$Property => $Value];
        } else {
            $array = $Property;
        }
        $this->serializeFields($array);

        parent::setField($RowID, $array);
    }
}
