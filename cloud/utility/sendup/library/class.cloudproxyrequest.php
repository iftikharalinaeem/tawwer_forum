<?php

/**
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @copyright 2009-2017 Vanilla Forums Inc.
 */

/**
 * CloudProxyRequest handler class
 *
 * This class abstracts the work of doing external requests.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 *
 * @package infrastructure
 * @subpackage cloudmonkey
 * @since 2.1.0
 */
class CloudProxyRequest {

    protected $cookieJar;

    public $maxReadSize = 4096;
    public $requestDefaults;
    public $requestHeaders;

    public $responseHeaders;
    public $responseStatus;
    public $responseBody;
    public $parsedBody;

    public $contentType;
    public $contentLength;
    public $connectionMode;

    protected $fileTransfer;
    protected $useSSL;
    protected $saveFile;
    protected $stream;
    public $actionLog;
    protected $options;
    protected $loud;

    public function __construct($loud = false, $requestDefaults = null) {
        $this->loud = $loud;

        $cookieKey = md5(mt_rand(0, 72312189) . microtime(true));
        if (defined('PATH_CACHE')) {
            $this->cookieJar = paths(PATH_CACHE, "cookiejar.{$cookieKey}");
        } else {
            $this->cookieJar = paths("/tmp", "cookiejar.{$cookieKey}");
        }

        if (!is_array($requestDefaults)) {
            $requestDefaults = array();
        }

        $defaults = array(
            'URL' => null,
            'Host' => null,
            'Method' => 'GET',
            'ConnectTimeout' => 5,
            'Timeout' => 5,
            'TransferMode' => 'normal', // or 'binary'
            'SaveAs' => null,
            'Stream' => null,
            'Redirects' => true,
            'SSLNoVerify' => false,
            'PreEncodePost' => true,
            'Cookies' => true, // Send my cookies?
            'CookieJar' => false, // Create a cURL CookieJar?
            'CookieSession' => false, // Should old cookies be trashed starting now?
            'CloseSession' => true, // Whether to close the session. Should always do this.
            'Redirected' => false, // Flag. Is this a redirected request?
            'Debug' => false, // Debug output on?
            'Simulate' => false       // Don't actually request, just set up
        );

        $this->requestDefaults = array_merge($defaults, $requestDefaults);
    }

    public function curlHeader(&$handler, $headerString) {
        $Line = explode(':', trim($headerString));
        $Key = trim(array_shift($Line));
        $Value = trim(implode(':', $Line));
        if (!empty($Key)) {
            $this->responseHeaders[$Key] = $Value;
        }
        return strlen($headerString);
    }

    protected function curlReceive(&$handler) {
        $this->responseHeaders = array();
        $response = curl_exec($handler);

        $this->responseStatus = curl_getinfo($handler, CURLINFO_HTTP_CODE);
        $this->contentType = strtolower(curl_getinfo($handler, CURLINFO_CONTENT_TYPE));
        $this->contentLength = (int) curl_getinfo($handler, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

        $requestHeaderInfo = trim(curl_getinfo($handler, CURLINFO_HEADER_OUT));
        $requestHeaderLines = explode("\n", $requestHeaderInfo);
        $request = trim(array_shift($requestHeaderLines));
        $this->requestHeaders['HTTP'] = $request;

        // Parse header status line
        foreach ($requestHeaderLines as $line) {
            $line = explode(':', trim($line));
            $key = trim(array_shift($line));
            $value = trim(implode(':', $line));
            $this->requestHeaders[$key] = $value;
        }
        $this->action(" Request Headers: " . print_r($this->requestHeaders, true));
        $this->action(" Response Headers: " . print_r($this->responseHeaders, true));

        if ($response === false) {
            $success = false;
            $this->responseBody = curl_error($handler);
            return $this->responseBody;
        }

        if ($this->options['TransferMode'] == 'normal') {
            $response = trim($response);
        }

        $this->responseBody = $response;

        // Parse response by Content-Type
        $parsedResponse = null;
        if (!$parsedResponse && $this->isContentType('application/json')) {
            $parsedResponse = json_decode($response, true);
        }
        if (!$parsedResponse && $this->isContentType('application/xml')) {
            $parsedResponse = simplexml_load_string($response);
        }

        // Assign to parsedBody
        if ($parsedResponse) {
            $this->parsedBody = $parsedResponse;
        }

        if ($this->saveFile) {

            // Get response and delete file if failed
            if ($this->responseStatus != '200') {
                $response = @file_get_contents($this->saveFile);
                @unlink($this->saveFile);
            }

            $success = file_exists($this->saveFile);
            $savedFileResponse = array(
                'Success' => $success,
                'Code' => $this->responseStatus,
                'Response' => $response
            );

            if ($success) {
                $savedFileResponse = array_merge($savedFileResponse, array(
                    'Time' => curl_getinfo($handler, CURLINFO_TOTAL_TIME),
                    'Speed' => curl_getinfo($handler, CURLINFO_SPEED_DOWNLOAD),
                    'Type' => curl_getinfo($handler, CURLINFO_CONTENT_TYPE),
                    'Size' => @filesize($this->saveFile),
                    'File' => $this->saveFile
                ));
            }

            if (curl_errno($handler)) {
                $savedFileResponse = array_merge($savedFileResponse, array(
                    'ErrNo' => curl_errno($handler),
                    'Error' => curl_error($handler)
                ));
            }

            $this->responseBody = json_encode($savedFileResponse);
        }

        return $this->responseBody;
    }

    /**
     * Send a request and receive the response
     *
     * Options:
     *   URL
     *   Host
     *   Method
     *   ConnectTimeout
     *   Timeout
     *   Redirects
     *   Cookies
     *   SaveAs
     *   CloseSession
     *   Redirected
     *   Debug
     *   Simulate
     *
     * @param type $options
     * @param array $params
     * @param array $files
     * @param type $headers
     * @return type
     */
    public function request($options = null, $params = null, $files = null, $headers = null) {

        /*
         * Allow requests that just want to use defaults to provide a string instead
         * of an optionlist.
         */

        if (is_string($options)) {
            $options = array('URL' => $options);
        }

        if (is_null($options)) {
            $options = array();
        }

        $this->options = $options = array_merge($this->requestDefaults, $options);

        $this->responseHeaders = array();
        $this->responseStatus = "";
        $this->responseBody = "";
        $this->parsedBody = array();
        $this->contentLength = 0;
        $this->contentType = '';
        $this->connectionMode = '';
        $this->actionLog = array();

        if (is_string($files)) {
            $files = array($files);
        }
        if (!is_array($files)) {
            $files = array();
        }
        if (!is_array($headers)) {
            $headers = array();
        }

        // Get the URL
        $relativeURL = val('URL', $options, null);
        if (is_null($relativeURL)) {
            $relativeURL = val('Url', $options, null);
        }

        if (is_null($relativeURL)) {
            throw new Exception("No URL provided");
        }

        $requestMethod = val('Method', $options);
        $forceHost = val('Host', $options);
        $followRedirects = val('Redirects', $options);
        $connectTimeout = val('ConnectTimeout', $options);
        $timeout = val('Timeout', $options);
        $saveAs = val('SaveAs', $options);
        $stream = val('Stream', $options);
        $transferMode = val('TransferMode', $options);
        $sslNoVerify = val('SSLNoVerify', $options);
        $preEncodePost = val('PreEncodePost', $options);
        $sendCookies = val('Cookies', $options);
        $cookieJar = val('CookieJar', $options);
        $cookieSession = val('CookieSession', $options);
        $closeSesssion = val('CloseSession', $options);
        $redirected = val('Redirected', $options);
        $debug = val('Debug', $options, false);
        $simulate = val('Simulate', $options);

        $oldVolume = $this->loud;
        if ($debug) {
            $this->loud = true;
        }

        $url = $relativeURL;
        $postData = $params;

        /*
         * If files were provided, preprocess the list and exclude files that don't
         * exist. Also, change the method to POST if it is currently GET and there
         * are valid files to send.
         */

        $sendFiles = array();
        foreach ($files as $file => $filePath) {
            if (file_exists($filePath)) {
                $sendFiles[$file] = $filePath;
            }
        }

        $this->fileTransfer = (bool) sizeof($sendFiles);
        if ($this->fileTransfer && $requestMethod != "PUT") {
            $this->options['Method'] = 'POST';
            $requestMethod = val('Method', $options);
        }

        /*
         * If extra headers were provided, preprocess the list into the correct
         * format for inclusion into both cURL and fsockopen header queues.
         */

        // Tack on Host header if forced
        if (!is_null($forceHost)) {
            $headers['Host'] = $forceHost;
        }

        $sendHeaders = array();
        foreach ($headers as $header => $headerVal) {
            $sendHeaders[] = "{$header}: {$headerVal}";
        }

        /*
         * If the request is being saved to a file, prepare to save to the
         * filesystem.
         */
        $this->saveFile = false;
        if ($saveAs) {
            $savePath = dirname($saveAs);
            $canSave = @mkdir($savePath, 0775, true);
            if (!is_writable($savePath)) {
                throw new Exception("Cannot write to save path: {$savePath}");
            }

            $this->saveFile = $saveAs;
        }

        /*
         * If the request is being streamed to the browser, set the file to
         * STDOUT
         */
        $this->stream = false;
        if ($stream) {
            if ($stream === true && !is_resource(STDOUT)) {
                throw new Exception("Cannot stream to STDOUT, it is closed");
            }
            $this->stream = is_resource($stream) ? $stream : STDOUT;
        }

        /*
         * Parse Query Parameters and collapse into a querystring in the case of
         * GETs.
         */

        $requestMethod = strtoupper($requestMethod);
        switch ($requestMethod) {
            case 'POST':
                break;

            case 'GET':
            default:
                $postData = is_array($postData) ? http_build_query($postData) : $postData;
                if (strlen($postData)) {
                    if (stristr($relativeURL, '?')) {
                        $url .= '&';
                    } else {
                        $url .= '?';
                    }
                    $url .= $postData;
                }
                break;
        }

        $this->action("Requesting {$url}");

        $urlParts = parse_url($url);

        // Extract scheme
        $scheme = strtolower(val('scheme', $urlParts, 'http'));
        $this->action(" scheme: {$scheme}");

        // Extract hostname
        $host = val('host', $urlParts, '');
        $this->action(" host: {$host}");

        // Extract / deduce port
        $port = val('port', $urlParts, null);
        if (empty($port)) {
            $port = ($scheme == 'https') ? 443 : 80;
        }
        $this->action(" port: {$port}");

        // Extract Path&Query
        $path = val('path', $urlParts, '');
        $query = val('query', $urlParts, '');
        $this->useSSL = ($scheme == 'https') ? true : false;

        $this->action(" transfer mode: {$transferMode}");

        /*
         * ProxyRequest can masquerade as the current user, so collect and encode
         * their current cookies as the default case is to send them.
         */

        $cookie = '';
        $encodeCookies = true;
        foreach ($_COOKIE as $key => $value) {
            if (strncasecmp($key, 'XDEBUG', 6) == 0) {
                continue;
            }

            if (strlen($cookie) > 0) {
                $cookie .= '; ';
            }

            $encodedValue = ($encodeCookies) ? urlencode($value) : $value;
            $cookie .= "{$key}={$encodedValue}";
        }

        // This prevents problems for sites that use sessions.
        if ($closeSesssion) {
            @session_write_close();
        }

        $response = '';

        $this->action("Parameters: " . print_r($postData, true));

        // We need cURL
        if (!function_exists('curl_init')) {
            throw new Exception('Encountered an error while making a request to the remote server: Your PHP configuration does not allow cURL requests.');
        }

        $handler = curl_init();
        curl_setopt($handler, CURLOPT_HEADER, false);
        curl_setopt($handler, CURLINFO_HEADER_OUT, true);
        curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handler, CURLOPT_USERAGENT, val('HTTP_USER_AGENT', $_SERVER, 'vanilla/agent'));
        curl_setopt($handler, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
        curl_setopt($handler, CURLOPT_HEADERFUNCTION, array($this, 'CurlHeader'));

        if ($transferMode == 'binary') {
            curl_setopt($handler, CURLOPT_BINARYTRANSFER, true);
        }

        if ($requestMethod != 'GET' && $requestMethod != 'POST') {
            curl_setopt($handler, CURLOPT_CUSTOMREQUEST, $requestMethod);
        }

        if ($cookieJar) {
            curl_setopt($handler, CURLOPT_COOKIEJAR, $this->cookieJar);
            curl_setopt($handler, CURLOPT_COOKIEFILE, $this->cookieJar);
        }

        if ($cookieSession) {
            curl_setopt($handler, CURLOPT_COOKIESESSION, true);
        }

        if ($followRedirects) {
            curl_setopt($handler, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($handler, CURLOPT_AUTOREFERER, true);
            curl_setopt($handler, CURLOPT_MAXREDIRS, 10);
        }

        if ($this->useSSL) {
            $this->action(" Using SSL");
            curl_setopt($handler, CURLOPT_SSL_VERIFYPEER, !$sslNoVerify);
            curl_setopt($handler, CURLOPT_SSL_VERIFYHOST, $sslNoVerify ? 0 : 2);
        }

        if ($timeout > 0) {
            curl_setopt($handler, CURLOPT_TIMEOUT, $timeout);
        }

        if ($cookie != '' && $sendCookies) {
            $this->action(" Sending client cookies");
            curl_setopt($handler, CURLOPT_COOKIE, $cookie);
        }

        if ($this->saveFile) {
            $this->action(" Saving to file: {$this->saveFile}");
            $fileHandle = fopen($this->saveFile, 'w+');
            curl_setopt($handler, CURLOPT_FILE, $fileHandle);
        }

        if ($this->stream) {
            $this->action(" Streaming to file descriptor");
            curl_setopt($handler, CURLOPT_FILE, $this->stream);
        }

        // Allow POST
        if ($requestMethod == 'POST') {
            if ($this->fileTransfer) {
                $this->action(" POSTing files");
                foreach ($sendFiles as $file => $filePath)
                    $postData[$file] = "@{$filePath}";
            } else {
                if ($preEncodePost && is_array($postData))
                    $postData = http_build_query($postData);
            }

            curl_setopt($handler, CURLOPT_POST, true);
            curl_setopt($handler, CURLOPT_POSTFIELDS, $postData);
        }

        // Allow PUT
        if ($requestMethod == 'PUT') {
            if ($this->fileTransfer) {
                $sendFile = val('0', $sendFiles);
                $sendFileSize = filesize($sendFile);
                $this->action(" PUTing file: {$sendFile}");
                $sendFileObject = fopen($sendFile, 'r');

                curl_setopt($handler, CURLOPT_PUT, true);
                curl_setopt($handler, CURLOPT_INFILE, $sendFileObject);
                curl_setopt($handler, CURLOPT_INFILESIZE, $sendFileSize);

                $sendHeaders[] = "Content-Length: {$sendFileSize}";
            }
        }

        // Any extra needed headers
        if (sizeof($sendHeaders)) {
            curl_setopt($handler, CURLOPT_HTTPHEADER, $sendHeaders);
        }

        // Set URL
        curl_setopt($handler, CURLOPT_URL, $url);
        curl_setopt($handler, CURLOPT_PORT, $port);

        $this->curlReceive($handler);

        if ($simulate) {
            return null;
        }

        curl_close($handler);

        $this->loud = $oldVolume;
        return $this->responseBody;
    }

    protected function action($message, $loud = null) {
        if ($this->loud || $loud) {
            echo "{$message}\n";
            flush();
            @ob_flush();
        }

        $this->actionLog[] = $message;
    }

    public function __destruct() {
        if (file_exists($this->cookieJar)) {
            @unlink($this->cookieJar);
        }
    }

    public function clean() {
        return $this;
    }

    /**
     * Check if the provided response matches the provided response type
     *
     * Class is a string representation of the HTTP status code, with 'x' used
     * as a wildcard.
     *
     * Class '2xx' = All 200-level responses
     * Class '30x' = All 300-level responses up to 309
     *
     * @param string $class
     * @return boolean Whether the response matches or not
     */
    public function responseClass($class) {
        $code = (string) $this->responseStatus;
        if (is_null($code)) {
            return false;
        }
        if (strlen($code) != strlen($class)) {
            return false;
        }

        for ($i = 0; $i < strlen($class); $i++) {
            if ($class{$i} != 'x' && $class{$i} != $code{$i}) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check for response content type
     *
     * @param string $type
     * @return boolean
     */
    public function isContentType($type) {
        $contentType = val('Content-Type', $this->responseHeaders);
        if (strpos($contentType, $type) !== false) {
            return true;
        }
        return false;
    }

    public function headers() {
        return $this->responseHeaders;
    }

    public function status() {
        return $this->responseStatus;
    }

    public function body() {
        return $this->responseBody;
    }

}
