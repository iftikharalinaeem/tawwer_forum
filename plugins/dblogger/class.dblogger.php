<?php
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */
/**
 * Database Logger
 *
 * Class DbLogger
 */
class DbLogger extends BaseLogger {

    /**
     * @var string The amount of time to delete logs after.
     */
    protected $pruneAfter;

    /**
     * Initialize a new instance of the {@link DbLogger} class.
     */
    public function __construct() {
        $this->setPruneAfter('-90 days');
    }

    /**
     * Extract known columns and save the rest as attributes.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null|void
     */
    public function log($level, $message, array $context = array()) {
        $columns = array(
            'id' => true,
            'userid' => true,
            'username' => true,
            'timestamp' => true,
            'ip' => true,
            'attributes' => true,
            'message' => true,
            'level' => true,
            'event' => true,
            'method' => true,
            'domain' => true,
            'path' => true
        );

        $attributes = array_diff_key($context, $columns);
        $insert = array_diff_key($context, $attributes);
        $insert['message'] = FormatString($message, $context);
        $insert['level'] = Logger::levelPriority($level);
        if ($attributes) {
            $insert['attributes'] = json_encode($attributes, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        }
        $insert['id'] = uniqid('', false).substr(dechex(mt_rand()), 0, 3);

        $r = Gdn::sqL()->Insert('EventLog', $insert);

        // Delete a couple of old logs.
        if ($timestamp = $this->getPruneAfterTimestamp()) {
            $px = Gdn::sql()->Database->DatabasePrefix;
            $sql = "delete from {$px}EventLog where timestamp <= :timestamp limit 10";
            $rd = Gdn::database()->query($sql, [':timestamp' => $timestamp]);
        }

        return $r;
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
                throw new InvalidArgumentException("Invalid timespan value for delete after.", 400);
            }
            if ($testTime >= $now) {
                throw new InvalidArgumentException("You must specify a timespan in the past for delete after.", 400);
            }
        }

        $this->pruneAfter = $pruneAfter;
        return $this;
    }
}
