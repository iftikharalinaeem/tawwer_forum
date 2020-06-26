#!/usr/bin/env php
<?php

/**
 * syncnode.php triggers synchronization events for all node sites on a multisite cluster
 *
 * This file reads the cluster configuration json file and extracts the API token, which it uses to prime the
 * calls to the hub sites.
 *
 * @version 1.0.0
 * @author Tim Gunter <tim@vanillaforums.com>
 * @license Proprietary
 * @copyright 2010-2014 Vanilla Forums Inc
 * @package multisite
 */

define('AGENT_CONFIG', '/usr/local/agent/conf/agent.json');
define('CLUSTER_CONFIG', '/var/www/api/conf/cluster.json');

try {

    // Load cluster config

    $clusterConfigData = file_get_contents(CLUSTER_CONFIG);
    if (!$clusterConfigData) {
        throw new Exception("Cluster configuration is empty", 500);
    }
    $clusterConfig = json_decode($clusterConfigData, true);
    if (!$clusterConfig) {
        throw new Exception("Cluster configuration is corrupt", 500);
    }

    // Load agent config

    $agentConfigData = file_get_contents(AGENT_CONFIG);
    if (!$agentConfigData) {
        throw new Exception("Agent configuration is empty", 500);
    }
    $agentConfig = json_decode($agentConfigData, true);
    if (!$agentConfig) {
        throw new Exception("Agent configuration is corrupt", 500);
    }

    // Check cluster state

    $mode = valr('cluster.loader.mode', $clusterConfig, null);
    if ($mode != 'multi') {
        throw new Exception("Not a multisite cluster", 400);
    }

    // Get multisite auth token

    $multisiteToken = valr('cluster.loader.apikey', $clusterConfig, null);
    if (!$multisiteToken) {
        throw new Exception("No multisite token found", 500);
    }

    // Get agent auth token

    $dataAccessToken = valr('server.api', $agentConfig, null);
    if (!$dataAccessToken) {
        throw new Exception("No agent apikey found", 500);
    }

    // Get cluster name

    $clusterName = valr('server.cluster', $agentConfig, null);
    $clusterNetwork = valr('server.network', $agentConfig, null);
    if (!$clusterName || !$clusterNetwork) {
        echo "Unknown cluster name or network\n";
        exit(1);
    }


} catch (Exception $ex) {
    echo $ex->getMessage()."\n";
    exit(1);
}

// CURL to cluster

$url = "https://data.{$clusterName}.{$clusterNetwork}/forum/callback";
$method = "POST";
$headers = [
    'Authorization' => "token {$multisiteToken}",
    'Content-Type' => 'application/json'
];

$params = [
    'path' => 'api/v1/multisites/syncnode.json',
    'method' => $method,
    'arguments' => null,
    'headers' => $headers,
    'secure' => true,
    'hub' => true,
    'nodes' => false
];

$payload = json_encode($params);

$command = ['curl'];
$command[] = "-X POST";
$command[] = "-H 'Content-Type: application/json'";
$command[] = "-H 'X-Access-Token: {$dataAccessToken}'";
$command[] = "--data-ascii '{$payload}'";
$command[] = "--insecure";
$command[] = "-s";
$command[] = "{$url}";

$command = implode(' ', $command);
$return = null;
$output = null;
exec($command, $output, $return);

if ($return) {
    echo "Failed to sync:\n";
    echo implode("\n",$output);
    exit($return);
}
exit(0);

/**
 * Return the value from an associative array or an object.
 * This function differs from val() in that $key can be a string consisting of dot notation that will be used to recursivly traverse the collection.
 *
 * @param string $key The key or property name of the value.
 * @param mixed $collection The array or object to search.
 * @param mixed $default The value to return if the key does not exist.
 * @return mixed The value from the array or object.
 */
function valr($key, $collection, $default = false) {
    $path = explode('.', $key);

    $value = $collection;
    for ($i = 0; $i < count($path); ++$i) {
        $subKey = $path[$i];

        if (is_array($value) && isset($value[$subKey])) {
            $value = $value[$subKey];
        } elseif (is_object($value) && isset($value->$subKey)) {
            $value = $value->$subKey;
        } else {
            return $default;
        }
    }
    return $value;
}
