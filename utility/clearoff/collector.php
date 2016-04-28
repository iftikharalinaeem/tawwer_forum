<?php
ob_start();
// Include Rackspace API
require_once(__DIR__.'/../../vendor/autoload.php');

use OpenCloud\Rackspace;

$exitCode = 1;

$options = getopt(null, [
    'container:',
    'prefix:',
    'rackspaceUser:',
    'rackspaceApiKey:',
    'rackspaceRegion:',
    'rackspaceFacing:',
    'objectsPathSource:',
]);

$container = $options['container'];
$prefix = $options['prefix'];
$rackspaceUser = $options['rackspaceUser'];
$rackspaceApiKey = $options['rackspaceApiKey'];
$rackspaceRegion = $options['rackspaceRegion'];
$rackspaceFacing = $options['rackspaceFacing'];
$objectsPathSource = $options['objectsPathSource'];

try {
    $client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, [
        'username' => $rackspaceUser,
        'apiKey'   => $rackspaceApiKey,
    ]);
    $objectStoreService = $client->objectStoreService(null, $rackspaceRegion, $rackspaceFacing);
    $container = $objectStoreService->getContainer($container);

    $jobsFileHandle = fopen($objectsPathSource, 'w');
    if ($jobsFileHandle) {
        $marker = '';
        $batch = 0;
        $eol = null;
        while ($marker !== null) {
            $params = [
                'marker' => $marker,
                'prefix' => $prefix,
            ];
            $count = 0;

            $objects = $container->objectList($params);

            $lastObjectName = null;
            foreach ($objects as $object) {
                $count++;
                $nonEncodedName = $object->getName();
                $lastObjectName = $nonEncodedName;
                $encodedName = rawurlencode($nonEncodedName);

                if ($nonEncodedName === $prefix) {
                    continue;
                }

                fwrite($jobsFileHandle, $eol.$encodedName);

                if ($eol === null) {
                    $eol = "\n";
                }
            }

            if ($count === 10000) {
                $marker = $lastObjectName;
            } else {
                $marker = null;
            }
        }

        fclose($jobsFileHandle);
    }
} catch (Exception $e) {
    echo $e->getMessage().PHP_EOL;
}
$content = ob_get_contents();
ob_end_flush();

exit(strlen($content) != 0 ? 1 : 0);
