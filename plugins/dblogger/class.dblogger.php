<?php
/**
 * @copyright 2014 Vanilla Forums Inc.
 * @license Proprietary
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
        );
        $attributes = array_diff_key($context, $columns);
        $insert = array_diff_key($context, $attributes);
        $insert['Message'] = FormatString($message, $context);
        if ($attributes) {
            $insert['Attributes'] = json_encode($attributes);
        }
        $insert['EventLogID'] = uniqid();

        return Gdn::SQL()->Insert('EventLog', $insert);
    }
}
