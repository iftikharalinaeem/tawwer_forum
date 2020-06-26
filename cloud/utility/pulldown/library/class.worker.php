<?php

if (!defined('APPLICATION'))
    exit();

/**
 * Worker
 *
 * Utility class that sets up worker job providers and makes sure they provide
 * the correct methods.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @license Proprietary
 * @copyright 2010, Vanilla Forums, Inc
 * @package discussionstats
 */
abstract class Worker {

    /**
     * 'worker' or 'sink'
     * @var string
     */
    public $type;

    /**
     * Worker ID
     * @var integer
     */
    public $id;

    /**
     *
     * @var Workers
     */
    protected $workers;

    /**
     * Build worker
     */
    public function __construct($workers) {
        $this->workers = $workers;
    }

    /**
     * Just before forking, do preparation
     */
    public static function prefork() {

        // NOOP
    }

    /**
     * Do work in child thread
     *
     */
    abstract public function work();
}
