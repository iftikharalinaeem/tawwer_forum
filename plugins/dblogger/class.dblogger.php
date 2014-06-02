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
            $insert['attributes'] = json_encode($attributes, JSON_UNESCAPED_SLASHES);
        }
        $insert['id'] = uniqid('', false).substr(dechex(mt_rand()), 0, 3);

        return Gdn::SQL()->Insert('EventLog', $insert);
    }
}
