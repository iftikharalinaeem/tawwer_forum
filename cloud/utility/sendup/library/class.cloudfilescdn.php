<?php

/**
 * Rackspace API Cloud Files CDN Functions
 *
 * Rackspace API functionality for Cloud Files CDN API.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @version 1.1
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 * @package infrastructure
 * @since 1.0
 */
class CloudFilesCDN extends Rackspace {

    public function __construct($AuthURL, $Identity) {
        parent::__construct($AuthURL, $Identity);

        $this->Service('CloudFilesCDN');
    }

    /**
     * Get container list
     *
     * container_name:
     *    'name'      - container name
     *    'objects'   - total number of objects in container
     *    'bytes'     - total number of stored bytes in container
     *
     * ...
     *
     * @return array
     */
    public function Containers() {
        $Containers = array();
        $Marker = null;
        $Limit = 1000;
        do {
            $Response = $this->Request('GET', '/', null, array(
                'format' => 'json',
                'limit' => $Limit,
                'marker' => $Marker
            ));

            $Received = sizeof($Response);
            if (!$Received) {
                break;
            }

            $Containers = array_column($Response, null, 'name');
            $Last = end($Containers);
            $Marker = $Last['name'];
        } while ($Limit && $Received == $Limit);
        return $Containers;
    }

    /* CONTAINER SERVICES */

    /**
     * Get container information
     *
     * 'objects'   - total number of objects
     * 'bytes'     - total number of stored bytes
     * 'meta'      - container meta data array
     *
     * @param string $Container
     * @return array
     */
    public function ContainerInfo($Container) {
        $Response = $this->Request('HEAD', "/{$Container}");

        $Info = array();
        $Info['objects'] = val('X-Container-Object-Count', $this->Rackspace->Headers());
        $Info['bytes'] = val('X-Container-Bytes-Used', $this->Rackspace->Headers());
        $Info['meta'] = $this->ParseMeta('Container', $this->Rackspace->Headers());

        return $Info;
    }

    /**
     * List objects in a container
     *
     * @param string $Container
     * @param array $Options
     * @return array
     */
    public function ListObjects($Container, $Options = null) {
        $DefaultOptions = array(
            'limit' => 1000,
            'marker' => null
        );
        if (!is_array($Options)) {
            $Options = array();
        }
        $Options = array_merge($DefaultOptions, $Options);

        $Options['format'] = 'json';

        $Objects = array();
        $Marker = val('marker', $Options);
        $Limit = val('limit', $Options);
        do {
            if ($Marker) {
                $Options['marker'] = $Marker;
            }
            $Response = $this->Request('GET', "/{$Container}", null, $Options);

            $Received = sizeof($Response);
            if (!$Received) {
                break;
            }

            $ObjectName = null;
            foreach ($Response as $Object) {
                $ObjectName = $Object['name'];
                $Objects[$ObjectName] = $Object;
            }
            $Marker = $ObjectName;
        } while ($Limit && $Received == $Limit);
        return $Objects;
    }

    /**
     * Create a container
     *
     * @param string $Container
     * @param array $Meta
     */
    public function CreateContainer($Container, $Meta = null) {
        if (is_null($Meta)) {
            $Meta = array();
        }

        $Response = $this->Request('PUT', "/{$Container}", null, null, $Meta);
    }

    /**
     * Delete a container
     *
     * @param string $Container
     */
    public function DeleteContainer($Container) {
        $Response = $this->Request('DELETE', "/{$Container}");
    }

    /* OBJECT SERVICES */

    /**
     * Retrieve an object, and optionally save it to the disk
     *
     * @param string $Container
     * @param string $Object
     * @param array $Options
     * @return array
     */
    public function RetrieveObject($Container, $Object, $Options = null) {
        if (!is_array($Options)) {
            $Options = array();
        }

        $DefaultOptions = array(
            'Debug' => false,
            'SaveAs' => false,      // Save to disk
            'Stream' => false,      // Stream to fd
            'Bypass' => true        // Don't bring a copy into the array
        );
        $Options = array_merge($DefaultOptions, $Options);

        if ($Options['Stream']) {
            $Options['Bypass'] = true;
            $Options['SaveAs'] = false;
        }

        if ($Options['SaveAs']) {
            $Options['Stream'] = false;
        }

        $RequestOptions = array(
            'Debug' => $Options['Debug'],
            'Binary' => true,
            'Stream' => $Options['Stream'],
            'SaveAs' => $Options['SaveAs']
        );

        try {
            $Response = $this->Request('GET', "/{$Container}/{$Object}", $RequestOptions);
        } catch (\Exception $Ex) {
            if ($Ex->getCode() == 404) {
                return false;
            }

            // Throw real problems
            throw $Ex;
        }
        $ObjectData = array();
        $ObjectData['name'] = $Object;
        $ObjectData['container'] = $Container;
        $ObjectData['data'] = $Response;
        $ObjectData['headers'] = $this->Rackspace->Headers();
        $ObjectData['meta'] = $this->ParseMeta('Object', $this->Rackspace->Headers());

        $SaveAs = $Options['SaveAs'];
        if ($SaveAs) {
            $ObjectData['path'] = $SaveAs;
            $ObjectData['hash'] = md5_file($SaveAs);
            if (!$Options['Bypass']) {
                $ObjectData['data'] = file_get_contents($SaveAs);
            }
        }

        return $ObjectData;
    }

    /**
     * Create/Update an object
     *
     * @param string $container Name of container
     * @param string $object Name of object
     * @param mixed $file File to store under object name
     * @param array $options Optional
     * @param array $meta Optional
     */
    public function PutObject($container, $object, $file, $options = null, $meta = null) {
        if (!is_array($options)) {
            $options = array();
        }
        if (!is_array($meta)) {
            $meta = array();
        }

        if (is_array($file)) {
            $file = array_pop($file);
        }

        $fileName = basename($file);
        $fp = pathinfo($fileName);
        $fileBase = $fp['filename'];
        $filePath = dirname($file);

        $splitThresholdBytes = 4 * 1024 * 1024 * 1024;  // 4 gigs
        if (key_exists('ChunkThreshold', $options)) {
            $splitThresholdBytes = $options['ChunkThreshold'];
        }

        $splitChunkBytes = 1 * 1024 * 1024 * 1024;      // 1 gig
        if (key_exists('ChunkSize', $options)) {
            $splitChunkBytes = $options['ChunkSize'];
        }

        $cleanup = val('Cleanup', $options, true);
        $reuse = val('Reuse', $options, false);

        $splitChunk = ($splitChunkBytes / pow(1024, 2)) . 'm';
        $fileSize = filesize($file);
        $isMulti = false;
        if ($fileSize > $splitThresholdBytes) {
            $isMulti = true;

            $segments = array();

            // Ensure backupdir still exists
            $segmentDir = paths($filePath, "{$fileBase}-segments");
            if (!is_dir($segmentDir)) {
                mkdir($segmentDir, 0755, true);
            }

            $splitTemplate = "part.";
            $splitPath = paths($segmentDir, $splitTemplate);
            $segmentsPrefix = "{$object}-segments";
            $globTemplate = "{$splitTemplate}*";
            $globPath = paths($segmentDir, $globTemplate);

            // If we're allowed to re-use, check if we already have a fully filled segment dir
            $mustSplit = true;
            if ($reuse) {
                $splitFiles = glob($globPath);
                $expectedFiles = ceil($fileSize / $splitThresholdBytes);

                // Found matching split files
                if (count($splitFiles) == $expectedFiles) {
                    $mustSplit = false;

                // Cleanup existing non matching split files
                } else {
                    $splitFiles = [];
                    foreach ($splitFiles as $splitFile) {
                        exec("rm -f {$splitFile}");
                    }
                }
            }

            if ($mustSplit) {
                $splitCommand = "split -b {$splitChunk} {$file} {$splitPath}";
                exec($splitCommand, $output, $return);
                if ($return) {
                    throw new Exception("split operation failed");
                }

                $splitFiles = glob($globPath);
            }

            // Gather split files
            sort($splitFiles);

            // Insert found split files into upfiles, indexed by segment prefix
            foreach ($splitFiles as $splitFile) {
                $splitFileName = basename($splitFile);
                $splitFileSegmentName = paths($segmentsPrefix, $splitFileName);
                $segments[$splitFileSegmentName] = $splitFile;
            }
        }

        if (!file_exists($file)) {
            throw new RackspaceAPIException("No such file '{$file}'", null, 404);
        }

        $headers = array();
        if (sizeof($meta)) {
            foreach ($meta as $metaKey => $metaValue) {
                $metaKey = str_replace(' ', '-', $metaKey);
                $headers["X-Object-Meta-{$metaKey}"] = $metaValue;
            }
        }

        // Add optional stuff
        if (array_key_exists('Content-Type', $options)) {
            $headers['Content-Type'] = $options['Content-Type'];
        }

        if (array_key_exists('Content-Disposition', $options)) {
            $headers['Content-Disposition'] = $options['Content-Disposition'];
        }

        // Automatically add 'Folder' constructs for slash-segmented files?
        $objectIsPath = stristr($object, '/') !== false;
        if ($objectIsPath && val('AutoFolders', $options, false) == true) {

            $folderHeaders = array(
                'Content-Type' => 'application/directory'
            );

            $trimmedObjectPath = trim($object, '/');
            $objectPaths = explode('/', $trimmedObjectPath);
            $path = array();
            $numPathElements = sizeof($objectPaths) - 2;
            for ($i = 0; $i < $numPathElements; $i++) {
                $path[] = $objectPaths[$i];
                $pathString = implode('/', $path);

                // Make this path a folder
                $requestOptions = array();
                $this->request(
                    'PUT',
                    "/{$container}/{$pathString}",
                    $requestOptions,
                    null,
                    $folderHeaders,
                    '/dev/null'
                );
            }
        }

        $objectData = array();

        if ($isMulti) {
            $files = $segments;
        } else {
            $files = array($object => $file);
        }

        $objects = array();

        // Upload segments
        $objectData = array();
        foreach ($files as $segmentName => $segmentFile) {

            $objectData = array();
            $requestOptions = array(
                'Timeout' => 0
            );
            $fullSegmentName = "/{$container}/{$segmentName}";

            $this->request('PUT', $fullSegmentName, $requestOptions, null, $headers, $segmentFile);
            $transferHeaders = $this->Rackspace->headers();

            $objectData['name'] = $segmentName;
            $objectData['size'] = filesize($segmentFile);
            $objectData['container'] = $container;
            $objectData['headers'] = $transferHeaders;
            $objectData['meta'] = $this->parseMeta('Object', $transferHeaders);

            $localHash = md5_file($segmentFile);
            $objectData['hash'] = val('Etag', $objectData['headers'], null);

            if (!is_null($objectData['hash']) && $localHash != $objectData['hash']) {
                throw new RackspaceAPIHashMismatchException("Failed to match hash while uploading data", $objectData);
            }

            $objects[] = $objectData;
        }

        // Manifest
        if ($isMulti) {

            $objectData = array();
            $requestOptions = array(
                'Timeout' => 0
            );
            $fullObjectName = "/{$container}/{$object}";

            $fullSegmentsPrefix = "{$container}/{$segmentsPrefix}";
            $headers['X-Object-Manifest'] = $fullSegmentsPrefix;

            $this->request('PUT', $fullObjectName, $requestOptions, null, $headers, '/dev/null');
            $transferHeaders = $this->Rackspace->headers();

            $objectData['name'] = $object;
            $objectData['size'] = $fileSize;
            $objectData['container'] = $container;
            $objectData['headers'] = $transferHeaders;
            $objectData['meta'] = $this->parseMeta('Object', $transferHeaders);
            $objectData['segments'] = $objects;

            // Delete multi segments
            if ($segmentDir && file_exists($segmentDir) && $cleanup) {
                exec("rm -rf {$segmentDir}");
            }
        }

        return $objectData;
    }

    /**
     * Create/Update an object using raw data instead of a file
     *
     * This is a convenience method that allows raw data to be uploaded to CloudFiles
     * instead of first creating a temporary file and uploading that. This method obviously
     * does *exactly* that, but insulates the caller from dealing with that complexity.
     *
     * @param string $Container
     * @param string $Object
     * @param string $Data
     * @param array $Options Optional
     * @param array $Meta Optional
     */
    public function PutData($Container, $Object, $Data, $Options = null, $Meta = null) {
        // Create temp file with data
        $TempKey = md5(mt_rand(0, 72312189) . microtime(true));
        $TempFile = CombinePaths(array("/tmp", "rackmonkey.data.{$TempKey}"));

        $DataLength = strlen($Data);
        $Written = file_put_contents($TempFile, $Data);
        if ($Written != $DataLength) {
            @unlink($TempFile);
            throw new RackspaceAPIException("Unable to write temporary file during PutData");
        }

        try {
            $PutObject = $this->PutObject($Container, $Object, array($TempFile), $Options, $Meta);
            @unlink($TempFile);
        } catch (\Exception $Ex) {
            @unlink($TempFile);
            throw $Ex;
        }

        return $PutObject;
    }

    /**
     * Copy an object
     *
     * @param type $Container
     * @param type $Object
     * @param type $NewContainer
     * @param type $NewObject
     * @param array $Meta
     * @return type
     */
    public function CopyObject($Container, $Object, $NewContainer, $NewObject, $Meta = null) {
        if (!is_array($Meta)) {
            $Meta = array();
        }

        $Headers = array();
        if (sizeof($Meta)) {
            foreach ($Meta as $MetaKey => $MetaValue) {
                $MetaKey = str_replace(' ', '-', $MetaKey);
                $Headers["X-Object-Meta-{$MetaKey}"] = $MetaValue;
            }
        }

        $Headers['Destination'] = "/{$NewContainer}/{$NewObject}";
        $this->Request('COPY', "/{$Container}/{$Object}", null, null, $Headers);
        $ObjectData['name'] = $NewObject;
        $ObjectData['container'] = $NewContainer;
        $ObjectData['headers'] = $this->Rackspace->Headers();
        $ObjectData['hash'] = val('Etag', $ObjectData['headers'], null);

        return $ObjectData;
    }

    /**
     * Delete an object
     *
     * @param string $Container
     * @param string $Object
     */
    public function DeleteObject($Container, $Object) {
        try {
            $this->Request('DELETE', "/{$Container}/{$Object}");
        } catch (\Exception $Ex) {
            if ($Ex->getCode() == 404) {
                return false;
            }

            // Throw real problems
            throw $Ex;
        }
    }

    /**
     * Move an object
     *
     * @param string $Container
     * @param string $Object
     * @param string $NewContainer
     * @param string $NewObject
     * @return array
     */
    public function MoveObject($Container, $Object, $NewContainer, $NewObject) {
        $ObjectData = $this->CopyObject($Container, $Object, $NewContainer, $NewObject);
        $this->DeleteObject($Container, $Object);

        return $ObjectData;
    }

    /**
     * Get object information
     *
     * @param string $Container
     * @param string $Object
     * @return array
     */
    public function ObjectInfo($Container, $Object) {
        try {
            $this->Request('HEAD', "/{$Container}/{$Object}");
        } catch (\Exception $Ex) {
            if ($Ex->getCode() == 404) {
                return false;
            }

            // Throw real problems
            throw $Ex;
        }

        $ObjectData = array();
        $ObjectData['name'] = $Object;
        $ObjectData['container'] = $Container;
        $ObjectData['bytes'] = val('Content-Length', $this->Rackspace->Headers());
        $ObjectData['type'] = val('Content-Type', $this->Rackspace->Headers());
        $ObjectData['modified'] = val('Last-Modified', $this->Rackspace->Headers());
        $ObjectData['hash'] = val('Etag', $this->Rackspace->Headers());
        $ObjectData['headers'] = $this->Rackspace->Headers();
        $ObjectData['meta'] = $this->ParseMeta('Object', $this->Rackspace->Headers());

        return $ObjectData;
    }

    /**
     * Update object meta information
     *
     * @param string $Container
     * @param string $Object
     * @param array $Meta
     */
    public function UpdateObjectInfo($Container, $Object, $Meta = null) {
        if (!is_array($Meta)) {
            $Meta = array();
        }

        $Headers = array();
        if (sizeof($Meta)) {
            foreach ($Meta as $MetaKey => $MetaValue) {
                $MetaKey = str_replace(' ', '-', $MetaKey);
                $Headers["X-Object-Meta-{$MetaKey}"] = $MetaValue;
            }
        }

        $this->Request('POST', "/{$Container}/{$Object}", null, null, $Headers);
    }

    /**
     *
     * @param type $MetaType
     * @param type $Headers
     * @return type
     */
    protected function ParseMeta($MetaType, $Headers) {
        $Meta = array();
        $MetaPrefix = "X-{$MetaType}-Meta";
        foreach ($Headers as $Header => $Value) {
            if (!StringBeginsWith($Header, $MetaPrefix)) {
                continue;
            }
            $MetaKey = trim(StringBeginsWith($Header, $MetaPrefix, false, true), '- ');
            $Meta[$MetaKey] = $Value;
        }

        return $Meta;
    }
}
