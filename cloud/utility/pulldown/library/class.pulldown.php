<?php

if (!defined('APPLICATION'))
    exit();

/**
 * PullDown
 *
 * Utility class that handles downloading a large number of files from a target.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @license Proprietary
 * @copyright 2010, Vanilla Forums, Inc
 */
class PullDown extends Worker {

    protected static $folder;
    protected $pid;
    protected $exited = false;
    protected static $workAddress;
    protected static $controlAddress;
    protected static $sinkAddress;

    /**
     * ZMQ context
     * @var ZMQContext
     */
    protected $zeromq;
    protected $zmqWork;
    protected $zmqControl;
    protected $zmqSink;

    /*
     * DATASOURCE
     */
    protected $dsn;
    protected $datasource;
    protected $dataopts;
    protected $curl;

    /*
     * SINK TRACKING
     */
    protected $transfers;

    const MESSAGE_WORK = 'work';
    const MESSAGE_FEEDBACK = 'feedback';
    const MESSAGE_END_STREAM = 'endstream';
    const MESSAGE_END_CLIENT = 'endclient';
    const MESSAGE_SUICIDE = 'suicide';

    /**
     * Prepare for forking and execution
     */
    public static function prefork() {

        $args = getopt('', array('folder:'));

        $folder = GetValue('folder', $args, false);
        if (!$folder) {
            throw new Exception("Required argument --folder not provided");
        }

        if (!file_exists($folder)) {
            throw new Exception("No such file or folder");
        }
        self::$folder = $folder;

        self::$workAddress = "tcp://127.0.01:7878";
        self::$controlAddress = "tcp://127.0.0.1:7879";
        self::$sinkAddress = "tcp://127.0.0.1:7880";
    }

    /**
     * Prepare for execution after fork
     *
     */
    public function prepare() {
        $this->zeromq = new ZMQContext();
        $this->pid = getmypid();

        switch ($this->type) {

            // Prepare data source for vent/sink workers
            case 'ventilator':
            case 'sink':

                $options = getopt("", array(
                    'dsn:'
                ));

                $datasource = null;
                if (array_key_exists('dsn', $options)) {
                    $this->dsn = $options['dsn'];
                }

                $this->datasource = false;
                switch ($this->dsn) {
                    case 'sftp':
                        break;

                    case 'stdin':
                        $this->datasource = fopen('php://stdin', 'r');
                        break;

                    case 'mysql':
                        $rOptions = array(
                            'host:', 'user:', 'password:', 'db:', 'table:'
                        );
                        $options = getopt("", $rOptions);
                        extract($options);

                        if (sizeof($options) < sizeof($rOptions)) {
                            Workers::log(Workers::LOG_L_FATAL, " missing required parameters for {$this->dsn} dsn");
                        } else {
                            $this->dataopts = $options;
                            $this->datasource = new mysqli($host, $user, $password, $db);
                        }
                        break;
                }

                // Keep track of some things in the sink process
                if ($this->type == 'sink') {
                    $this->transfers = array(
                        'total' => 0,
                        'success' => 0,
                        'failed' => 0,
                        'rxkBps' => 0,
                        'rxkB' => 0,
                        'last' => 0,
                        'recent' => array()
                    );
                }

                break;

            // Prepare downloader for worker
            case 'worker':
                $this->curl = curl_init();
                curl_setopt($this->curl, CURLOPT_HEADER, false);
                curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, false);
                curl_setopt($this->curl, CURLOPT_USERAGENT, 'PullDown/1.0');
                curl_setopt($this->curl, CURLOPT_PORT, 80);

                // Follow redirects
                curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($this->curl, CURLOPT_AUTOREFERER, true);
                curl_setopt($this->curl, CURLOPT_MAXREDIRS, 10);

                // Ignore SSL issues
                curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);

                // Timeout
                curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($this->curl, CURLOPT_TIMEOUT, 30);
                break;
        }
    }

    /**
     * Do work
     */
    public function work() {
        sleep(1);

        switch ($this->type) {
            case 'ventilator':
                $this->ventilate();
                break;

            case 'sink':
                $this->sink();
                break;

            case 'worker':
                $this->worker();
                break;
        }
    }

    /**
     * Work scheduler
     */
    protected function ventilate() {
        Workers::log(Workers::LOG_L_THREAD, " ventilator active", Workers::LOG_O_SHOWPID);

        // Trap ventilator kills
        pcntl_signal(SIGUSR2, array($this, 'signal'));

        // Prepare zeromq context
        $this->prepare();

        $this->zmqWork = $this->zeromq->getSocket(ZMQ::SOCKET_PUSH);
        $this->zmqWork->setSockOpt(ZMQ::SOCKOPT_HWM, 10);
        $this->zmqWork->bind(self::$workAddress);

        $this->zmqControl = $this->zeromq->getSocket(ZMQ::SOCKET_SUB);
        $this->zmqControl->setSockOpt(ZMQ::SOCKOPT_SUBSCRIBE, '');
        $this->zmqControl->connect(self::$controlAddress);

        // Create jobs
        $this->generate();

        // Wait for job consumption
        $this->waitventilator();
    }

    /**
     * Generate new jobs
     *
     * Read from db or STDIN and push jobs to queue
     */
    public function generate() {

        // Add normal jobs
        if ($this->datasource) {

            switch ($this->dsn) {
                case 'mysql':

                    $table = $this->dataopts['table'];

                    $allocation = 0;
                    do {
                        $allocation++;
                        $alloc = "{$this->pid}.{$allocation}";
                        $result = $this->datasource->query("select * from {$table} where downloaded=2 and allocated='{$alloc}'");
                        if (!$result->num_rows) {

                            // Allocate 1000 rows
                            $this->datasource->query("update {$table} set downloaded=2,allocated='{$alloc}' where downloaded=0 and allocated is null limit 1000");
                            $result = $this->datasource->query("select * from {$table} where downloaded=2 and allocated='{$alloc}'");
                        }

                        // Loop and add jobs
                        $nJobs = $result->num_rows;
                        while ($job = $result->fetch_assoc()) {
                            $url = parse_url($job['url']);
                            $path = $url['path'];
                            $local = CombinePaths(array(self::$folder, $path));
                            $job = array_merge($job, array(
                                'task' => 'download',
                                'id' => $job['jobid'],
                                'path' => $path,
                                'local' => $local
                            ));
                            $this->addjob($job);
                        }
                    } while ($nJobs);
                    break;

                case 'stdin':
                    break;
            }
        }

        // Add black job
        $this->zmqWork->send($this->message(self::MESSAGE_END_STREAM));

        Workers::log(Workers::LOG_L_APP, " jobs added", Workers::LOG_O_SHOWPID);
    }

    /**
     * Add a job to the work queue
     *
     * @param type $payload
     */
    protected function addjob($payload) {
        $this->zmqWork->send($this->message(self::MESSAGE_WORK, $payload));
    }

    /**
     * Wait for jobs to be consumed
     *
     */
    public function waitventilator() {

        $poll = new ZMQPoll();
        $poll->add($this->zmqControl, ZMQ::POLL_IN);
        $read = $write = array();

        $continue = true;
        do {
            // Trigger signals
            pcntl_signal_dispatch();

            $messages = array();

            // Wait for events
            try {

                $events = $poll->poll($read, $write, 1000);
                foreach ($read as $socket) {
                    $messages[] = $socket->recv();
                }

                // No event
            } catch (ZMQException $ex) {
                continue;
            }

            // Process all received messages
            $exit = false;
            $suicide = false;
            foreach ($messages as $message) {

                $message = json_decode($message, true);
                $msgType = GetValue('type', $message, null);
                $messagePayload = GetValue('payload', $message);

                switch ($msgType) {

                    case self::MESSAGE_SUICIDE:
                        Workers::log(Workers::LOG_L_QUEUE, " {$this->type}: suicide", Workers::LOG_O_SHOWPID);
                        $suicide = true;
                        break;
                }
            }

            if ($suicide)
                $exit = true;

            if ($exit) {
                $this->exited = true;
                $this->zmqWork->unbind(self::$workAddress);
                $this->zmqControl->disconnect(self::$controlAddress);
                exit(0);
            }
        } while ($continue);
    }

    /**
     * Results tabulator
     *
     */
    protected function sink() {
        Workers::log(Workers::LOG_L_THREAD, " sink active", Workers::LOG_O_SHOWPID);

        // Trap sink kills
        pcntl_signal(SIGUSR2, array($this, 'signal'));

        // Prepare zeromq context
        $this->prepare();

        $this->zmqControl = $this->zeromq->getSocket(ZMQ::SOCKET_PUB);
        $this->zmqControl->bind(self::$controlAddress);

        $this->zmqSink = $this->zeromq->getSocket(ZMQ::SOCKET_PULL);
        $this->zmqSink->bind(self::$sinkAddress);

        // Receive results
        $this->waitsink();
    }

    /**
     * Wait for processes
     *
     * Allow processes to do their work, return result, and be shut down.
     */
    public function waitsink() {

        $poll = new ZMQPoll();
        $poll->add($this->zmqSink, ZMQ::POLL_IN);
        $read = $write = array();

        $handledIDs = array();

        $continue = true;
        do {
            // Trigger signals
            pcntl_signal_dispatch();

            $messages = array();

            // Wait for events
            try {

                $events = $poll->poll($read, $write, 1000);
                foreach ($read as $socket) {
                    $messages[] = $socket->recv();
                }

                // No event
            } catch (ZMQException $ex) {
                continue;
            }

            // Process all received messages
            foreach ($messages as $message) {

                $message = json_decode($message, true);
                $msgType = GetValue('type', $message, null);
                $messagePayload = GetValue('payload', $message);

                switch ($msgType) {

                    case self::MESSAGE_FEEDBACK:
                        $this->feedback($messagePayload);
                        break;

                    // Handle end of client
                    case self::MESSAGE_END_CLIENT:
                        Workers::log(Workers::LOG_L_QUEUE, " received end of client", Workers::LOG_O_SHOWPID);
                        $eof = true;
                        break;

                    // Handle end of stream
                    case self::MESSAGE_END_STREAM:
                        Workers::log(Workers::LOG_L_QUEUE, " received end of stream", Workers::LOG_O_SHOWPID);
                        $eof = true;

                        // Tell parent to stop forking
                        $parentPid = Workers::$parentPid;
                        $sent = posix_kill($parentPid, SIGUSR1);
                        $status = $sent ? "success" : "failed";
                        if (!$sent) {
                            $error = posix_get_last_error();
                            $status = posix_strerror($error);
                        }
                        Workers::log(Workers::LOG_L_THREAD, " shutdown parent (pid: {$parentPid}): {$status}", Workers::LOG_O_SHOWPID);
                        sleep(1);

                        // Tell clients to die
                        $this->zmqControl->send($this->message(self::MESSAGE_SUICIDE));
                        Workers::log(Workers::LOG_L_QUEUE, " sent suicide to workers", Workers::LOG_O_SHOWPID);

                        break;
                }
            }
        } while ($continue);
    }

    /**
     * Receive feedback
     *
     * @param array $payload
     */
    public function feedback($payload) {
        $id = GetValue('id', $payload, null);
        $url = GetValue('url', $payload, null);
        $status = GetValue('status', $payload, 'failed');
        $code = GetValue('code', $payload, 500);
        $path = GetValue('path', $payload, 'none');
        $error = GetValue('error', $payload, '');
        $success = ($status == 'success');

        $decoratedCode = $success ? "\033[0;32m{$code}\033[0m" : "\033[0;31m{$code}\033[0m";
        Workers::log(Workers::LOG_L_QUEUE, sprintf("  %s %s %s", $decoratedCode, $path, $error), Workers::LOG_O_SHOWPID);

        try {

            $this->transfers['total'] ++;
            switch ($status) {
                case 'success':
                    $this->transfers['success'] ++;
                    $this->transfers['rxkB'] += ($payload['size'] / 1024);
                    $this->transfers['recent'][] = $payload;

                    // See if we need to recalculate speed
                    $lastSpeed = time() - $this->transfers['last'];
                    if ($lastSpeed >= 3) {
                        $start = null;
                        $end = null;
                        $bytes = 0;
                        foreach ($this->transfers['recent'] as $xfer) {
                            if (is_null($start) || $xfer['start'] < $start) {
                                $start = $xfer['start'];
                            }

                            if (is_null($end) || $xfer['end'] > $end) {
                                $end = $xfer['end'];
                            }

                            $bytes += $xfer['size'];
                        }
                        $dur = $end - $start;
                        if ($dur) {
                            $speed = $bytes / $dur;
                        } else {
                            $speed = 0;
                        }

                        $rxkBps = round(($speed / 1024), 2);
                        $this->transfers['rxkBps'] = $rxkBps;
                        $this->transfers['recent'] = array();
                        $this->transfers['last'] = time();

                        // Output
                        $success = $this->transfers['success'];
                        $failed = $this->transfers['failed'];
                        $total = $this->transfers['total'];
                        $speed = number_format($this->transfers['rxkBps']);
                        Workers::log(Workers::LOG_L_APP, "\n      \033[0;32m{$success}\033[0m / {$total} ok, \033[0;31m{$failed}\033[0m failed - avg: {$speed} kBps\n");
                    }
                    break;

                case 'failed':
                default:
                    $this->transfers['failed'] ++;
                    break;
            }
        } catch (Exception $ex) {

        }

        // Update
        $table = $this->dataopts['table'];
        $downloaded = $success ? 1 : -1;
        $this->datasource->query("update {$table} set downloaded={$downloaded}, allocated=null, responsecode={$code} where jobid={$id}");
    }

    /**
     * Cleanup, output and die
     */
    public function end() {

        if ($this->type == 'sink') {

            // Output
            $success = $this->transfers['success'];
            $failed = $this->transfers['failed'];
            $total = $this->transfers['total'];
            $speed = number_format($this->transfers['rxkBps']);
            Workers::log(Workers::LOG_L_APP, "\n      \033[0;32m{$success}\033[0m / {$total} ok, \033[0;31m{$failed}\033[0m failed - avg: {$speed} kBps\n");
        }

        Workers::log(Workers::LOG_L_THREAD, " {$this->type}: suiciding", Workers::LOG_O_SHOWPID);
        exit(0);
    }

    /**
     * Catch signals
     *
     * @param integer $signal
     */
    public function signal($signal) {
        Workers::log(Workers::LOG_L_THREAD, " {$this->type}: signal '{$signal}'", Workers::LOG_O_SHOWPID);
        switch ($signal) {
            // Cleanup and die
            case SIGUSR2:
                $this->end();
                break;
        }
    }

    /**
     * Work executor
     *
     */
    protected function worker() {
        Workers::log(Workers::LOG_L_APP, " worker active", Workers::LOG_O_SHOWPID);

        // Prepare zeromq context
        $this->prepare();

        $this->zmqWork = $this->zeromq->getSocket(ZMQ::SOCKET_PULL);
        $this->zmqWork->setSockOpt(ZMQ::SOCKOPT_HWM, 1);
        $this->zmqWork->connect(self::$workAddress);

        $this->zmqControl = $this->zeromq->getSocket(ZMQ::SOCKET_SUB);
        $this->zmqControl->setSockOpt(ZMQ::SOCKOPT_SUBSCRIBE, '');
        $this->zmqControl->connect(self::$controlAddress);

        $this->zmqSink = $this->zeromq->getSocket(ZMQ::SOCKET_PUSH);
        $this->zmqSink->connect(self::$sinkAddress);

        // Run jobs
        $this->run();
    }

    /**
     * Run delegated jobs
     */
    public function run() {

        // Get jobs
        $poll = new ZMQPoll();
        $poll->add($this->zmqWork, ZMQ::POLL_IN);
        $poll->add($this->zmqControl, ZMQ::POLL_IN);
        $read = $write = array();

        $handled = 0;
        $continue = true;
        do {
            $messages = array();

            // Wait for events
            try {

                $events = $poll->poll($read, $write, 1000);
                foreach ($read as $socket) {
                    $messages[] = $socket->recv();
                }

            // No event
            } catch (ZMQException $ex) {
                Workers::log(Workers::LOG_L_QUEUE, " worker received no events", Workers::LOG_O_SHOWPID);
            }

            // Process all received messages
            $exit = false;
            $suicide = false;
            foreach ($messages as $message) {

                $message = json_decode($message, true);
                $msgtype = GetValue('type', $message, null);

                switch ($msgtype) {

                    // Handle a real job
                    case self::MESSAGE_WORK:
                        $handled++;
                        $messagePayload = GetValue('payload', $message);
                        $messageID = GetValue('id', $messagePayload);
                        //Workers::log(Workers::LOG_L_QUEUE, " processing message ({$messagePayload})", Workers::LOG_O_SHOWPID);
                        // Exec payload
                        $this->job($messagePayload);
                        break;

                    // Handle end of stream
                    case self::MESSAGE_END_STREAM:
                        Workers::log(Workers::LOG_L_QUEUE, " end of message stream", Workers::LOG_O_SHOWPID);

                        // Tell sink about end of stream
                        $this->zmqSink->send($this->message(self::MESSAGE_END_STREAM));
                        break;

                    case self::MESSAGE_SUICIDE:
                        Workers::log(Workers::LOG_L_QUEUE, " {$this->type}: suicide", Workers::LOG_O_SHOWPID);
                        $suicide = true;
                        break;
                }
            }

            if ($this->workers->jobsPerWorker > 0 && $handled >= $this->workers->jobsPerWorker) {
                // Tell sink about end of client
                $this->zmqSink->send($this->message(self::MESSAGE_END_CLIENT));
                Workers::log(Workers::LOG_L_THREAD, " worker cycling", Workers::LOG_O_SHOWPID);
                $exit = true;
            }

            if ($suicide) {
                $exit = true;
            }

            if ($exit) {
                $this->exited = true;
                //$this->zmqWork->disconnect(self::$workAddress);
            }

            if ($this->exited) {
                $this->zmqControl->disconnect(self::$controlAddress);
                $this->zmqSink->disconnect(self::$sinkAddress);
                exit(0);
            }
        } while ($continue);
    }

    /**
     * Execute a job
     *
     * @param array $job
     */
    public function job($job) {

        $url = $job['url'];
        $path = $job['path'];
        $local = $job['local'];

        $response = array(
            'id' => $job['id'],
            'url' => $url,
            'local' => $local,
            'path' => $path,
            'start' => microtime(true)
        );

        try {
            // Provide URL
            curl_setopt($this->curl, CURLOPT_URL, $url);

            // Provide file handle
            $localDir = dirname($local);
            @mkdir($localDir, 0755, true);
            $fh = @fopen($local, 'w');
            if (!$fh) {
                throw new Exception('failed to create file');
            }
            curl_setopt($this->curl, CURLOPT_FILE, $fh);

            // Download
            curl_exec($this->curl);
            fclose($fh);
            $response = array_merge($response, array(
                'code' => $code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE),
                'speed' => $speed = curl_getinfo($this->curl, CURLINFO_SPEED_DOWNLOAD),
                'size' => $size = curl_getinfo($this->curl, CURLINFO_SIZE_DOWNLOAD),
            ));

            $status = ($code == 200) ? 'success' : 'failed';
            if ($code != 200) {
                $response['error'] = curl_error($this->curl);
            }
        } catch (Exception $ex) {

            $status = 'failed';
            $response['error'] = $ex->getMessage();
        }

        $end = microtime(true);
        $response = array_merge($response, array(
            'status' => $status,
            'end' => $end,
            'dur' => $response['start'] - $end
        ));

        $this->zmqSink->send($this->message(self::MESSAGE_FEEDBACK, $response));
    }

    /**
     *
     * @param type $type
     * @param type $payload
     */
    protected function message($type, $payload = null) {
        $message = array(
            'type' => $type,
            'worker' => array(
                'id' => $this->id,
                'pid' => $this->pid
            )
        );

        if ($payload) {
            $message['payload'] = $payload;
        }

        return json_encode($message);
    }

}
