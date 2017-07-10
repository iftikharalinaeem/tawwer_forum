<?php
/**
 * A plugin that integrates the php debug bar.
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

use DebugBar\DebugBar;
use DebugBar\DataCollector\MessagesCollector;
use Vanilla\DebugBar\ExceptionsCollector;
use Vanilla\DebugBar\LoggerCollector;

/**
 * Handles integration of the [PHP Debug Bar](http://phpdebugbar.com/) with Vanilla.
 */
class DebugBarPlugin extends Gdn_Plugin {
    /// Properties ///

    /**
     * @var \DebugBar\DebugBar
     */
    protected $debugBar;

    /// Methods ///

    /**
     * Initialize a new instance of the {@link \DebugBarPlugin} class.
     */
    public function __construct() {
        parent::__construct();
        require_once __DIR__.'/vendor/autoload.php';
    }

    /**
     * Add the application traces to a message collector.
     *
     * @param MessagesCollector $messages The collector to add the messages to.
     * @param ExceptionsCollector $exceptions The collector to add exceptions to.
     */
    public function addTraces(MessagesCollector $messages, ExceptionsCollector $exceptions) {
        $traces = trace();
        if (!is_array($traces)) {
            return;
        }

        $strings = [];
        foreach ($traces as $info) {
            list($message, $type) = $info;

            if ($message instanceof \Exception) {
                $exceptions->addException($message);
                continue;
            }

            if (!is_string($message)) {
                $message = $messages->getDataFormatter()->formatVar($message);
            }
            switch ($type) {
                case TRACE_ERROR:
                    $messages->error($message);
                    break;
                case TRACE_INFO:
                    $messages->info($message);
                    break;
                case TRACE_NOTICE:
                    $messages->notice($message);
                    break;
                case TRACE_WARNING:
                    $messages->warning($message);
                    break;
                default:
                    $messages->debug("$type: $message");
            }
        }
    }

    /**
     * Get the debug bar for the application.
     *
     * @return \DebugBar\DebugBar Returns the debug bar instance.
     */
    public function debugBar() {
        if ($this->debugBar === null) {
            $this->debugBar = new DebugBar();

            $this->debugBar->addCollector(new \DebugBar\DataCollector\PhpInfoCollector());
            $this->debugBar->addCollector(new LoggerCollector('messages'));
            $this->debugBar->addCollector(new \DebugBar\DataCollector\RequestDataCollector());
            $this->debugBar->addCollector(new \DebugBar\DataCollector\TimeDataCollector());
            $this->debugBar->addCollector(new \DebugBar\DataCollector\MemoryCollector());
            $this->debugBar->addCollector(new ExceptionsCollector());

            $db = Gdn::database();
            if (method_exists($db, 'addCollector')) {
                $db->addCollector($this->debugBar);
            }

            $this->debugBar->addCollector(new \DebugBar\DataCollector\ConfigCollector([], 'data'));

            Logger::addLogger($this->debugBar['messages']);
        }
        return $this->debugBar;
    }

    /**
     * Get the javascript script includer for the debug bar.
     *
     * @return \DebugBar\JavascriptRenderer Returns the javascript script includer for the debug bar.
     */
    public function jsRenderer() {
        $baseUrl = Gdn::request()->assetRoot().'/plugins/debugbar/vendor/maximebf/debugbar/src/DebugBar/Resources';
        $jsRenderer = $this->debugBar()->getJavascriptRenderer($baseUrl);

        $jsRenderer->addAssets(
            [],
            asset('/plugins/debugbar/js/debugbar.js', false, true)
        );

        return $jsRenderer;
    }

    /// Event Handlers ///

    /**
     * Add the debug bar's javascript after the body.
     */
    public function base_afterBody_handler() {
        $bar = $this->debugBar();
        $this->addTraces($bar['messages'], $bar['exceptions']);

        $body = $this->jsRenderer()->render();
        echo $body;
    }

    /**
     * Finish off the controller timing and add the debug bar asset to the page.
     *
     * @param Gdn_Controller $sender The event sender.
     */
    public function base_render_before($sender) {
        static $called = false;

        if ($called) {
            return;
        }

        $bar = $this->debugBar();
        $bar['time']->stopMeasure('controller');

        $bar['data']->setData($sender->Data);


        $bar['time']->startMeasure('render', 'Render');

        if (!$sender->Head) {
            return;
        }

        if (val('HTTP_X_REQUESTED_WITH', $_SERVER) === 'XMLHttpRequest') {
            $path = Gdn::request()->path();
            if (!in_array($path, ['dashboard/notifications/inform', 'settings/analyticstick.json'])) {
                $this->debugBar()->sendDataInHeaders();
            }
        } else {
            $head = $this->jsRenderer()->renderHead();
            $sender->AddAsset('Head', $head, 'debugbar-head');
            $sender->addCssFile('debugbar.css', 'plugins/debugbar');
        }
        $called = true;
    }

    /**
     * Start the timing of the controller method.
     */
    public function gdn_dispatcher_beforeControllerMethod_handler() {
        static $called = false;

        if ($called) {
            return;
        }

        $bar = $this->debugBar();
        $bar['time']->stopMeasure('dispatch');
        $bar['time']->startMeasure('controller', 'Controller');
        $called = true;
    }

    /**
     * Start the debug bar timings as soon as possible.
     */
    public function gdn_pluginManager_afterStart_handler() {
        // Install the debugger database.
        $tmp = Gdn::factoryOverwrite(true);
        Gdn::factoryInstall(Gdn::AliasDatabase, '\Vanilla\DebugBar\DatabaseDebugBar', __DIR__.'/src/DatabaseDebugBar.php', Gdn::FactorySingleton, ['Database']);
        Gdn::factoryOverwrite($tmp);
        unset($tmp);

        $bar = $this->debugBar();
        /* @var \DebugBar\DataCollector\TimeDataCollector $timer */
        $timer = $bar['time'];

        $now = microtime(true);
        $timer->addMeasure('Bootstrap', val('REQUEST_TIME_FLOAT', $_SERVER, $now), $now);
        $timer->startMeasure('dispatch', 'Dispatch');
    }
}
