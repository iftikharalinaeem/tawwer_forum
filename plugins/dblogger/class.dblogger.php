<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 */

use Psr\Log\LoggerInterface;

/**
 * Database Logger
 *
 * Class DbLogger
 */
class DbLogger implements LoggerInterface {
    use \Psr\Log\LoggerTrait;

    /**
     * @var string The amount of time to delete logs after.
     */
    protected $pruneAfter;

    /**
     * @var Gdn_SQLDriver $sql
     */
    private $sql;

    /**
     * Initialize a new instance of the {@link DbLogger} class.
     */
    public function __construct(Gdn_SQLDriver $sql = null) {
        if ($sql === null) {
            $sql = Gdn::sql();
            $sql = clone $sql;
            $sql->reset();
        }
        $this->sql = $sql;

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

        $r = $this->sql->insert('EventLog', $insert);

        // Delete a couple of old logs.
        if ($timestamp = $this->getPruneAfterTimestamp()) {
            $px = $this->sql->Database->DatabasePrefix;
            $sql = "delete from {$px}EventLog where timestamp <= :timestamp limit 10";
            $rd = $this->sql->Database->query($sql, [':timestamp' => $timestamp]);
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
                throw new InvalidArgumentException('Invalid timespan value for "prune after".', 400);
            }
            if ($testTime >= $now) {
                throw new InvalidArgumentException('You must specify a timespan in the past for "prune after".', 400);
            }
        }

        $this->pruneAfter = $pruneAfter;
        return $this;
    }
}
