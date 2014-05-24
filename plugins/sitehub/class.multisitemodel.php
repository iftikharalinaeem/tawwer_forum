<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license Proprietary
 */

class MultisiteModel extends Gdn_Model {
    /// Properties ///

    /**
     * @var MultisiteModel
     */
    protected static $instance;

    /**
     * @var string
     */
    protected $siteNameFormat;

    /**
     * @var string
     */
    protected $siteUrlFormat;

    /// Methods ///

    public function __construct($name = '') {
        parent::__construct('Multisite');

        $this->Validation->AddRule('Slug', 'regex:`[a-z0-9-]`');
        $this->Validation->ApplyRule('Slug', 'Slug', 'The slug must consist of numbers, lowercase letters or a slash.');

        $this->siteNameFormat = C('SiteHub.SiteNameFormat', '%s.vanillaforums.com');
        $this->siteUrlFormat = C('SiteHub.SiteUrlFormat', '//%s.vanillaforums.com');
    }

    /**
     * Gets the singleton instance of this class.
     *
     * @return MultisiteModel Returns the singleton instance of this class.
     */
    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new MultisiteModel();
        }
        return self::$instance;
    }

    public function build($id) {
        if (!class_exists('Communication')) {
            $this->status($id, 'error', 'Communication with the orchestration server is not enabled.');
            return false;
        }

        $site = $this->getID($id);
        if (!$site) {
            throw NotFoundException('Site');
        }

        $valid = true;

        if (!in_array($site['Status'], ['pending', 'error'])) {
            $this->Validation->AddValidationResult('Status', 'Cannot build a site that isn\'t pending.');
            $valid = false;
        }

        if (!$valid) {
            return false;
        }

        $name = sprintf($this->siteNameFormat(), $site['Slug']);

        $buildQuery = Communication::orchestration('/site/createnode')
            ->method('post')
            ->parameter('name', $name)
//            ->parameter('siteid', $importSite['SiteID'])
            ->parameter('accountid', Infrastructure::site('accountid'))
            ->parameter('domain', Gdn::Request()->Host())
//            ->parameter('cluster', Infrastructure::cluster())
//            ->parameter('planid', $plan['FeatureID'])
//            ->parameter('adminname', Gdn::Session()->User->Name)
//            ->parameter('adminpassword', BetterRandomString(16, 'Aa0'))
//            ->parameter('adminemail', Gdn::Session()->User->Email)
            ->parameter('callbackurl', Gdn::Request()->Domain()."/hub/multisites/{$site['MultisiteID']}/buildcallback?access_token=".urlencode(Infrastructure::clusterConfig('cluster.loader.apikey', '')));
        $build = $buildQuery->send();

        if ($buildQuery->responseClass('2xx')) {
            // The site built.
            $this->SetField($id, [
                'Status' => 'building',
                'DateStatus' => Gdn_Format::ToDateTime()
            ]);
            return true;
        } elseif ($buildQuery->code() == 409) {
            // The site already existed.
            $this->status($id, 'active');
        } else {
            // The site has an error.
            $error = $buildQuery->errorMsg();
            $this->status($id, 'error', $error);
            return false;
        }
    }

    /**
     * @param string $slug The site slug.
     * @return string Returns the url of the site.
     */
    public function siteUrl($slug, $withDomain) {
        if (IsUrl($slug)) {
            throw new Gdn_UserException("$slug is not a valid slug.", 422);
        }
        $result = sprintf($this->siteUrlFormat, $slug);
        if ($withDomain) {
            if (!IsUrl($result)) {
                $result = Gdn::Request()->Domain().'/'.ltrim($result, '/');
            }
        }
        return $result;
    }

    /**
     * Update the status of the site.
     *
     * @param int $id The id of the site.
     * @param string $status The new status.
     * @param string $error An error message for an error status.
     */
    public function status($id, $status, $error = '') {
        $site = $this->getID($id);
        $attributes = $site['Attributes'];
        $set = [
            'Status' => $status,
            'DateStatus' => Gdn_Format::ToDateTime()
        ];

        if ($error) {
            $attributes['Error'] = $error;
            $set['Attributes'] = $attributes;
        } elseif (isset($site['Attributes']['Error'])) {
            unset($attributes['Error']);
            $set['Attributes'] = $attributes;
        }

        $this->SetField($id, $set);
    }

    public function insert($fields) {
        // Sites can only be inserted in the pending status.
        $fields['Status'] = 'pending';
        $fields['DateStatus'] = Gdn_Format::ToDateTime();

        $slug = val('Slug', $fields);

        if ($slug) {
            TouchValue('Name', $fields, sprintf($this->siteNameFormat, $slug));
            TouchValue('Url', $fields, $this->siteUrl($slug, false));
        }


        $result = parent::Insert($fields);

        if ($result) {
            // Now we need to queue the site to build.
            $this->build($result);
        }

        return $result;
    }

    public function Update($fields, $where = FALSE, $limit = FALSE) {
        // Clean out the fields that are in the schema, but not allowed to be updated.
        unset(
            $fields['Name'],
            $fields['Slug'],
            $fields['Url'],
            $fields['Status'],
            $fields['DateStatus']
        );

        return parent::Update($fields);
    }

    public function calculateRow(&$row) {
        $url = $row['Url'];
        if (IsUrl($url)) {
            $row['FullUrl'] = $url;
        } else {
            $row['FullUrl'] = Gdn::Request()->Domain().$url;
        }

        if (isset($row['Attributes']) && is_string($row['Attributes'])) {
            $row['Attributes'] = @unserialize($row['Attributes']);
        }
        if (!isset($row['Attributes']) || !is_array($row['Attributes'])) {
            $row['Attributes'] = [];
        }
    }

    public function getID($id) {
        $row = parent::GETID($id, DATASET_TYPE_ARRAY);
        if ($row) {
            $this->calculateRow($row);
        }
        return $row;
    }

    public function getWhere($where = FALSE, $orderFields = '', $orderDirection = 'asc', $limit = FALSE, $offset = FALSE) {
        if (!$limit) {
            $limit = 1000;
        }

        if (empty($where)) {
            $rows = parent::Get($orderFields, $orderDirection, $limit, PageNumber($offset, $limit));
        } else {
            $rows = parent::GetWhere($where, $orderFields, $orderDirection, $limit, $offset);
        }
        array_walk($rows->ResultArray(), [$this, 'calculateRow']);
        return $rows;
    }

    /**
     * Make an api call out to a node..
     *
     * @param string $node The slug of the node to call out to.
     * @param string $path The path to the api endpoint.
     * @param string $method The http method to use.
     * @param array $params The parameters for the request, either get or post.
     * @return mixed Returns the decoded response from the request.
     * @throws Gdn_UserException Throws an exception when the api endpoint returns an error response.
     */
    public function nodeApi($node, $path, $method = 'GET', $params = []) {
        $node = trim($node, '/');

        Trace("api: $method /$node$path");

        $headers = [];

        // Kludge for osx that doesn't allow host files.
        $baseUrl = $this->siteUrl($node, true);
        $urlParts = parse_url($baseUrl);

        if ($urlParts['host'] === 'localhost' || StringEndsWith($urlParts['host'], '.lc')) {
            $headers['Host'] = $urlParts['host'];
            $urlParts['host'] = '127.0.0.1';
        }

        $url = rtrim(http_build_url($baseUrl, $urlParts), '/').'/api/v1/'.ltrim($path, '/');

        if ($access_token = Infrastructure::clusterConfig('cluster.loader.apikey', '')) {
//            $params['access_token'] = C('Plugins.SimpleAPI.AccessToken');
            $headers['Authentication'] = "token $access_token";
        }

        $request = new ProxyRequest();
        $response = $request->Request([
            'URL' => $url,
            'Cookies' => false,
            'Timeout' => 100,
        ], $params, null, $headers);

        if ($request->ContentType === 'application/json') {
            $response = json_decode($response, true);
        }

        if ($request->ResponseStatus != 200) {
            Trace($response, "Error {$request->ResponseStatus}");
            throw new Gdn_UserException('api: '.val('Exception', $response, 'There was an error performing your request.'), $request->ResponseStatus);
        }

        Trace($response, "hub api response");
        return $response;
    }

    /**
     * Saves a site attribute to the database.
     *
     * This is a convenience method that uses $this->SaveToSerializedColumn().
     *
     * @param int The site ID to save.
     * @param string|array The name of the attribute being saved, or an associative array of name => value pairs to be saved. If this is an associative array, the $Value argument will be ignored.
     * @param mixed The value being saved.
     * @return bool Whether or not the attribute as saved.
     */
    public function SaveAttribute($siteID, $attribute, $value = '') {
        return $this->SaveToSerializedColumn('Attributes', $siteID, $attribute, $value);
    }

    /**
     * Search for sites based on a search string.
     *
     * @param string $search
     * @param string $orderFields
     * @param string $orderDirection
     * @param int|bool $limit
     * @param int|bool $offset
     * @return Gdn_DataSet
     */
    public function search($search, $orderFields = '', $orderDirection = 'asc', $limit = FALSE, $offset = FALSE) {
        if (!$search) {
            return $this->getWhere(false, $orderFields, $orderDirection, $limit, $offset);
        }

        $this->SQL
            ->BeginWhereGroup()
            ->OrLike('Name', $search)
            ->OrLike('Url', $search)
            ->EndWhereGroup();

        return $this->getWhere(false, $orderFields, $orderDirection, $limit, $offset);
    }

    public function syncNode($node) {
        if (is_array($node)) {
            $nodeSlug = $node['Slug'];
        } else {
            $nodeSlug = $node;
        }

        $result = $this->nodeApi($nodeSlug, '/utility/syncnode.json', 'POST');
        return $result;
    }

    public function syncNodes($where = [], $limit = 0, $offset = 0) {
        $result = [];

        if (isset($where['search'])) {
            $nodes = $this->search($where['search'], '', '', $limit, $offset)->ResultArray();
        } else {
            $nodes = $this->getWhere($where, '', '', $limit, $offset)->ResultArray();
        }

        foreach ($nodes as $node) {
            try {
                $nodeResult = $this->syncNode($node);
                $result[$node['Slug']] = $nodeResult;
            } catch (Exception $ex) {
                $result[$node['Slug']] = (string)$ex->getCode();
            }
        }

        return $result;
    }
}