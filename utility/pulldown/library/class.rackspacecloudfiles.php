<?php

if (!defined('APPLICATION'))
    exit();

/**
 * Rackspace API Cloud Files Functions
 *
 * Rackspace API functionality for Cloud Files API.
 *
 *    // Configure Rackspace
 *    $CloudFiles = new RackspaceCloudFiles();
 *    $CloudFiles->Account('Vanilla');
 *    $CloudFiles->Service('CloudFiles');
 *    $CloudFiles->PreferredRegion('DFW');
 *    $CloudFiles->Context('internal');
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @version 1.1
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 * @package Api
 * @since 1.0
 */
class RackspaceCloudFiles extends RackspaceAPI {

    public function __construct() {
        parent::__construct();
    }

    /* ACCOUNT SERVICES */

    /**
     * Get account information
     *
     * 'containers' - total number of containers
     * 'objects'    - total number of objects
     * 'bytes'      - total number of stored bytes
     * 'meta'       - account meta data array
     *
     * @return array
     */
    public function AccountInfo() {
        $Response = $this->Request('HEAD', '/');

        $Info = array();
        $Info['containers'] = GetValue('X-Account-Container-Count', $this->Rackspace->ResponseHeaders);
        $Info['objects'] = GetValue('X-Account-Object-Count', $this->Rackspace->ResponseHeaders);
        $Info['bytes'] = GetValue('X-Account-Bytes-Used', $this->Rackspace->ResponseHeaders);
        $Info['meta'] = $this->ParseMeta('Account', $this->Rackspace->ResponseHeaders);

        return $Info;
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
        $Marker = NULL;
        $Limit = 1000;
        do {
            $Response = $this->Request('GET', '/', NULL, array(
                'format' => 'json',
                'limit' => $Limit,
                'marker' => $Marker
            ));

            $Received = sizeof($Response);
            if (!$Received)
                break;

            $ContainerName = NULL;
            foreach ($Response as $Container) {
                $ContainerName = $Container['name'];
                $Containers[$ContainerName] = $Container;
            }
            $Marker = $ContainerName;
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
        $Info['objects'] = GetValue('X-Container-Object-Count', $this->Rackspace->ResponseHeaders);
        $Info['bytes'] = GetValue('X-Container-Bytes-Used', $this->Rackspace->ResponseHeaders);
        $Info['meta'] = $this->ParseMeta('Container', $this->Rackspace->ResponseHeaders);

        return $Info;
    }

    /**
     * List objects in a container
     *
     * @param string $Container
     * @param array $Options
     * @return array
     */
    public function ListObjects($Container, $Options = NULL) {
        $DefaultOptions = array(
            'limit' => $Limit,
            'marker' => $Marker
        );
        if (!is_array($Options))
            $Options = array();
        $Options = array_merge($DefaultOptions, $Options);

        $Options['format'] = 'json';

        $Objects = array();
        $Marker = NULL;
        $Limit = 1000;
        do {
            $Response = $this->Request('GET', "/{$Container}", NULL, $Options);

            $Received = sizeof($Response);
            if (!$Received)
                break;

            $ObjectName = NULL;
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
    public function CreateContainer($Container, $Meta = NULL) {
        if (is_null($Meta))
            $Meta = array();

        $Response = $this->Request('PUT', "/{$Container}", NULL, NULL, $Meta);
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
    public function RetrieveObject($Container, $Object, $Options = NULL) {
        if (!is_array($Options))
            $Options = array();

        $DefaultOptions = array(
            'SaveAs' => FALSE,
            'Bypass' => TRUE
        );
        $Options = array_merge($DefaultOptions, $Options);

        $RequestOptions = array(
            'Binary' => TRUE
        );

        $RequestOptions['SaveAs'] = $Options['SaveAs'];

        try {
            $Response = $this->Request('GET', "/{$Container}/{$Object}", $RequestOptions);
        } catch (Exception $Ex) {
            if ($Ex->getCode() == 404)
                return FALSE;

            // Throw real problems
            throw $Ex;
        }
        $ObjectData = array();
        $ObjectData['name'] = $Object;
        $ObjectData['container'] = $Container;
        $ObjectData['data'] = $Response;
        $ObjectData['headers'] = $this->Rackspace->ResponseHeaders;
        $ObjectData['meta'] = $this->ParseMeta('Object', $this->Rackspace->ResponseHeaders);

        $SaveAs = $Options['SaveAs'];
        if ($SaveAs) {
            $ObjectData['path'] = $SaveAs;
            $ObjectData['hash'] = md5_file($SaveAs);
            if (!$Options['Bypass'])
                $ObjectData['data'] = file_get_contents($SaveAs);
        }

        return $ObjectData;
    }

    /**
     * Create/Update an object
     *
     * @param string $Container
     * @param string $Object
     * @param mixed $Files
     * @param array $Options Optional
     * @param array $Meta Optional
     */
    public function PutObject($Container, $Object, $Files, $Options = NULL, $Meta = NULL) {
        if (!is_array($Files))
            $Files = array($Files);

        $MultiSegment = (sizeof($Files) > 1);

        foreach ($Files as &$File) {
            if (!file_exists($File))
                throw new RackspaceAPIException("No such segment '{$File}'", NULL, 404);
        }

        if (!is_array($Options))
            $Options = array();

        if (!is_array($Meta))
            $Meta = array();

        $Headers = array();
        if (sizeof($Meta)) {
            foreach ($Meta as $MetaKey => $MetaValue) {
                $MetaKey = str_replace(' ', '-', $MetaKey);
                $Headers["X-Object-Meta-{$MetaKey}"] = $MetaValue;
            }
        }

        // Add optional stuff
        if (array_key_exists('Content-Type', $Options))
            $Headers['Content-Type'] = $Options['Content-Type'];
        if (array_key_exists('Content-Disposition', $Options))
            $Headers['Content-Disposition'] = $Options['Content-Disposition'];

        // Automatically add 'Folder' constructs for slash-segments files?
        $ObjectIsPath = stristr($Object, '/') !== FALSE;
        if ($ObjectIsPath && GetValue('AutoFolders', $Options, FALSE) == TRUE) {

            $FolderHeaders = array(
                'Content-Type' => 'application/directory'
            );

            $TrimmedObjectPath = trim($Object, '/');
            $ObjectPaths = explode('/', $TrimmedObjectPath);
            $Path = array();
            $NumPathElements = sizeof($ObjectPaths) - 2;
            for ($i = 0; $i < $NumPathElements; $i++) {
                $Path[] = $ObjectPaths[$i];
                $PathString = implode('/', $Path);

                // Make this path a folder
                $RequestOptions = array();
                $this->Request('PUT', "/{$Container}/{$PathString}", $RequestOptions, NULL, $FolderHeaders, '/dev/null');
            }
        }

        $ObjectData = array();

        // Upload segments
        foreach ($Files as $SegmentName => $File) {

            $SegmentObjectName = $Object;
            if ($MultiSegment)
                $SegmentObjectName = $SegmentName;

            $ObjectData = array();
            $RequestOptions = array();
            $SegmentObjectNameEncoded = "/{$Container}/{$SegmentObjectName}";

            $this->Request('PUT', $SegmentObjectNameEncoded, $RequestOptions, NULL, $Headers, $File);
            $ObjectData['name'] = $SegmentObjectName;
            $ObjectData['container'] = $Container;
            $ObjectData['headers'] = $this->Rackspace->ResponseHeaders;
            $ObjectData['meta'] = $this->ParseMeta('Object', $this->Rackspace->ResponseHeaders);

            $LocalHash = md5_file($File);
            $ObjectData['hash'] = GetValue('Etag', $ObjectData['headers'], NULL);

            if (!is_null($ObjectData['hash']) && $LocalHash != $ObjectData['hash'])
                throw new RackspaceAPIHashMismatchException("Failed to match hash while uploading data", $ObjectData);
        }

        // Manifest
        if ($MultiSegment) {

            $ObjectData = array();
            $RequestOptions = array();
            $ObjectNameEncoded = "/{$Container}/{$Object}";

            $this->Request('PUT', $ObjectNameEncoded, $RequestOptions, NULL, $Headers, '/dev/null');
            $ObjectData['name'] = $Object;
            $ObjectData['container'] = $Container;
            $ObjectData['headers'] = $this->Rackspace->ResponseHeaders;
            $ObjectData['meta'] = $this->ParseMeta('Object', $this->Rackspace->ResponseHeaders);
            $ObjectData['segments'] = sizeof($Files);
        }

        return $ObjectData;
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
    public function PutData($Container, $Object, $Data, $Options = NULL, $Meta = NULL) {
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
        } catch (Exception $Ex) {
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
    public function CopyObject($Container, $Object, $NewContainer, $NewObject, $Meta = NULL) {
        if (!is_array($Meta))
            $Meta = array();

        $Headers = array();
        if (sizeof($Meta)) {
            foreach ($Meta as $MetaKey => $MetaValue) {
                $MetaKey = str_replace(' ', '-', $MetaKey);
                $Headers["X-Object-Meta-{$MetaKey}"] = $MetaValue;
            }
        }

        $Headers['Destination'] = "/{$NewContainer}/{$NewObject}";
        $this->Request('COPY', "/{$Container}/{$Object}", NULL, NULL, $Headers);
        $ObjectData['name'] = $NewObject;
        $ObjectData['container'] = $NewContainer;
        $ObjectData['headers'] = $this->Rackspace->ResponseHeaders;
        $ObjectData['hash'] = GetValue('Etag', $ObjectData['headers'], NULL);

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
        } catch (Exception $Ex) {
            if ($Ex->getCode() == 404)
                return FALSE;

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
        } catch (Exception $Ex) {
            if ($Ex->getCode() == 404)
                return FALSE;

            // Throw real problems
            throw $Ex;
        }

        $ObjectData = array();
        $ObjectData['name'] = $Object;
        $ObjectData['container'] = $Container;
        $ObjectData['bytes'] = GetValue('Content-Length', $this->Rackspace->ResponseHeaders);
        $ObjectData['type'] = GetValue('Content-Type', $this->Rackspace->ResponseHeaders);
        $ObjectData['modified'] = GetValue('Last-Modified', $this->Rackspace->ResponseHeaders);
        $ObjectData['hash'] = GetValue('Etag', $this->Rackspace->ResponseHeaders);
        $ObjectData['headers'] = $this->Rackspace->ResponseHeaders;
        $ObjectData['meta'] = $this->ParseMeta('Object', $this->Rackspace->ResponseHeaders);

        return $ObjectData;
    }

    /**
     * Update object meta information
     *
     * @param string $Container
     * @param string $Object
     * @param array $Meta
     */
    public function UpdateObjectInfo($Container, $Object, $Meta = NULL) {
        if (!is_array($Meta))
            $Meta = array();

        $Headers = array();
        if (sizeof($Meta)) {
            foreach ($Meta as $MetaKey => $MetaValue) {
                $MetaKey = str_replace(' ', '-', $MetaKey);
                $Headers["X-Object-Meta-{$MetaKey}"] = $MetaValue;
            }
        }

        $this->Request('POST', "/{$Container}/{$Object}", NULL, NULL, $Headers);
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
            if (!StringBeginsWith($Header, $MetaPrefix))
                continue;
            $MetaKey = trim(StringBeginsWith($Header, $MetaPrefix, FALSE, TRUE), '- ');
            $Meta[$MetaKey] = $Value;
        }

        return $Meta;
    }

}
