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
            'EventLogID' => true,
            'InsertUserID' => true,
            'InsertName' => true,
            'TimeInserted' => true,
            'InsertIPAddress' => true,
            'Attributes' => true,
            'Message' => true,
            'LogLevel' => true,
            'Event' => true,
            'Method' => true,
            'Domain' => true,
            'Path' => true
        );
        $attributes = array_diff_key($context, $columns);
        $insert = array_diff_key($context, $attributes);
        $insert['Message'] = FormatString($message, $context);
        if ($attributes) {
            $insert['Attributes'] = json_encode($attributes, JSON_UNESCAPED_SLASHES);
        }
        $insert['EventLogID'] = uniqid('', true);
        $insert['LogLevel'] = $level;

        return Gdn::SQL()->Insert('EventLog', $insert);
    }
}
