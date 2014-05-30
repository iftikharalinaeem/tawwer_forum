<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license Proprietary
 */

class Syslogger extends BaseLogger {
    protected $format;

    public $extra = [];

    public function __construct($format = 'json', $ident = 'com.vanilla', $option = LOG_ODELAY, $facility = LOG_LOCAL0) {
        $this->format = $format;
        $opened = openlog($ident, $option, $facility);
    }

    public function __destruct() {
        $closed = closelog();
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
        if ($this->format === 'json') {
            $this->logJson($level, $message, $context);
        } else {
            $this->logMessage($level, $message, $context);
        }
    }

    protected function logMessage($level, $message, array $context = array()) {
        $realMessage = FormatString($message, $context);
        $priority = Logger::levelPriority($level);

        if ($event = val('Event', $context)) {
            $realMessage = "<$event> $realMessage";
        }
        $realMessage = rtrim($realMessage, '.').'.';

        $url = trim(val('Method', $context).' '.val('Domain', $context).val('Path', $context));
        if ($url)
            $realMessage .= ' '.$url;

        syslog($priority, $realMessage);
    }

    protected function logJson($level, $message, array $context = array()) {
        $msg = FormatString($message, $context);
        $context = array_change_key_case($context, CASE_LOWER);

        // Add the standard fields to the row.
        $row = [
            'event' => val('event', $context),
            'msg' => $msg,
            'username' => $context['insertname'],
            'userid' => $context['insertuserid'],
            'ip' => $context['insertipaddress'],
            'method' => $context['method'],
            'domain' => $context['domain'],
            'path' => $context['path']
        ];

        $tags = array_merge((array)val('tags', $context, []), explode('_', $row['event']));
        $row['tags'] = $tags;

        // Remove the standard fields.
        unset($context['event'], $context['insertname'], $context['insertuserid'], $context['insertipaddress'],
            $context['method'], $context['domain'], $context['timeinserted'], $context['path'], $context['tags'], $context['responsebody']);

        foreach ($context as $key => $val) {
            if (!isset($row[$key])) {
                $row[$key] = $val;
            }
        }

        if ($this->extra) {
            $row += $this->extra;
        }

        $json = json_encode($row, JSON_UNESCAPED_SLASHES);
        syslog(Logger::levelPriority($level), $json);
    }
}