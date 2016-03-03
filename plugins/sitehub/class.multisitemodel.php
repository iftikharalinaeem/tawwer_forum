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

    protected $reservedSlugs = ['hub'];

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

        $this->Validation->AddRule('Slug', 'func:validate_slug');
        $this->Validation->ApplyRule('Slug', 'Slug', 'The slug must consist of numbers, lowercase letters or a slash.');

        // Determine url and name formats
        $multi = Infrastructure::getMulti(Infrastructure::site('name'));
        if (is_array($multi)) {
            $this->siteNameFormat = $multi['template'];
            $this->siteUrlFormat = $multi['slugtemplate'];
        } else {
            $this->siteNameFormat = '%s.vanillaforums.com';
            $this->siteUrlFormat = '//%s.vanillaforums.com';
        }
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

    /**
     * Gets the cluster api key.
     *
     * @param bool $header If true the key will be returned in a way suitable to be passed in an Authorization header.
     * @return string Returns the api key or an empty string if the cluster doesn't have one.
     */
    public static function apikey($header = false) {
        $key = (string)Infrastructure::clusterConfig('cluster.loader.apikey', '');
        if ($key && $header) {
            $key = "token $key";
        }
        return $key;
    }

    /**
     * Queue a site for deletion.
     *
     * @param string $id The MultisiteID of the site.
     * @return bool Returns true if the site was successfully queued for delete.
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function queueDelete($id) {
        if (!class_exists('Communication')) {
            throw new Gdn_UserException('Communication with the orchestration server is not enabled.', 500);
        }

        $site = $this->getID($id);
        if (!$site) {
            throw NotFoundException('Site');
        }

        $valid = true;

        if (!in_array($site['Status'], ['active', 'error'])) {
            $this->Validation->AddValidationResult('Status', 'Cannot delete a site that isn\'t active.');
            $valid = false;
        }

        if (!$valid) {
            return false;
        }

        $deleteQuery = Communication::orchestration('/site/delete')
            ->method('post')
            ->parameter('siteid', val('SiteID', $site))
            ->parameter('callback', [
                'url' => Gdn::Request()->Domain()."/hub/api/v1/multisites/{$site['MultisiteID']}/deletecallback.json",
                'headers' => [
                    'Authorization' => self::apikey(true)
                ]
            ]);
        $deleteResponse = $deleteQuery->send();

        if ($deleteQuery->responseClass('2xx')) {
            // The site built.
            $this->SetField($id, [
                'Status' => 'deleting',
                'DateStatus' => Gdn_Format::ToDateTime(),
                'SiteID' => valr('site.SiteID', $deleteResponse)
            ]);
            return true;
        } else {
            // The site has an error.
            $error = $deleteQuery->code().' '.$deleteQuery->errorMsg();
            $this->Validation->AddValidationResult('MultisiteID', '@'.$error);
            return false;
        }
    }

    /**
     * Make sure a slug is unique.
     *
     * @param string $slug The slug to validate
     * @return bool Returns true if the slug is unique or false otherwise.
     */
    public function validateSlugUnique($slug) {
        if (in_array(strtolower($slug), $this->reservedSlugs)) {
            $this->Validation->AddValidationResult('Slug', "The slug '$slug' is already in use by another site.");
            return false;
        }
        if ($this->getWhere(['Slug' => $slug])->NumRows()) {
            $this->Validation->AddValidationResult('Slug', "The slug '$slug' is already in use by another site.");
            return false;
        }
        return true;
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

        $name = sprintf($this->siteNameFormat, $site['Slug']);

        $buildQuery = Communication::orchestration('/site/createnode')
            ->method('post')
            ->parameter('name', $name)
            ->parameter('accountid', Infrastructure::site('accountid'))
            ->parameter('domain', Gdn::Request()->Host()) // TODO: make work for non-dirs
            ->parameter('flavor', 'node')
            ->parameter('callback', [
                'url' => Gdn::Request()->Domain()."/hub/api/v1/multisites/{$site['MultisiteID']}/buildcallback.json",
                'headers' => [
                    'Authorization' => self::apikey(true)
                ]
            ]);
        $build = $buildQuery->send();

        if ($buildQuery->responseClass('2xx')) {
            // The site built.
            $this->SetField($id, [
                'Status' => 'building',
                'DateStatus' => Gdn_Format::ToDateTime(),
                'SiteID' => valr('site.SiteID', $build)
            ]);
            return true;
        } elseif ($buildQuery->code() == 409 && val('site', $build)) {
            // The site already existed.
            $this->status($id, 'active');
            $siteID = valr('site.SiteID', $build);
            if ($siteID) {
                $this->SetField($id, 'SiteID', $siteID);
            }
        } else {
            // The site has an error.
            $error = $buildQuery->errorMsg();
            $this->status($id, 'error', $error);
            return false;
        }
    }

    /**
     * Format a node slug into a url.
     *
     * @param string $slug The site slug.
     * @return string Returns the url of the site.
     */
    public function siteUrl($slug, $withDomain) {
        if (isUrl($slug)) {
            throw new Gdn_UserException("$slug is not a valid slug.", 422);
        }
        $result = sprintf($this->siteUrlFormat, $slug);

        if ($withDomain) {
            if (!isUrl($result)) {
                $result = paths(Gdn::request()->domain(), $result);
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
        $result = true;

        // Sites can only be inserted in the pending status.
        $fields['Status'] = 'pending';
        $fields['DateStatus'] = Gdn_Format::ToDateTime();

        $slug = val('Slug', $fields);

        if ($slug) {
            TouchValue('Name', $fields, sprintf($this->siteNameFormat, $slug));
            TouchValue('Url', $fields, $this->siteUrl($slug, false));

            // Check to see if the slug already exists.
            $result = $this->validateSlugUnique($slug);
        }

        if ($result) {
            $result = parent::Insert($fields);
        }

        if ($result) {
            // Now we need to queue the site to build.
            $this->build($result);
        }

        return $result;
    }

    public function Update($fields, $where = FALSE, $limit = FALSE) {
        // Clean out the fields that are in the schema, but not allowed to be updated.
        unset(
//            $fields['Name'],
//            $fields['Slug'],
            $fields['Url'],
            $fields['Status'],
            $fields['DateStatus']
        );

        return parent::Update($fields, $where, $limit);
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

    public function getFromUrl($url) {
        $slug = $this->slugFromUrl($url);
        $row = $this->getWhere(['Slug' => $slug])->FirstRow(DATASET_TYPE_ARRAY);
        return $row;
    }

    public function slugFromUrl($url) {
        $format = $this->siteUrlFormat;

        if (!IsUrl($format)) {
            $slug = trim(parse_url($url, PHP_URL_PATH), '/');
        } else {
            $domain = parse_url($url, PHP_URL_HOST);
            $slug = trim(strstr($domain, '.', true). '.');
        }

        return $slug;
    }

    /**
     * {@inheritdoc}
     */
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
     * Look up a site row from one of its three keys.
     *
     * @param int $multisiteID The multisite ID of the site.
     * @param int $siteID The infrastructure ID of the site.
     * @param string $slug The slug of the site.
     * @return array|false Returns the multisite row or **false** if one isn't found.
     */
    public function getSiteFromKey($multisiteID, $siteID = 0, $slug = '') {
        if (!empty($multisiteID)) {
            $where = ['MultisiteID' => $multisiteID];
        } elseif (!empty($siteID)) {
            $where = ['SiteID' => $siteID];
        } elseif (!empty($slug)) {
            $where = ['Slug' => $slug];
        } else {
            return false;
        }

        return $this->getWhere($where)->firstRow(DATASET_TYPE_ARRAY);
    }

    /**
     * Get a list of roles to sync with a node.
     *
     * @param array|int $site The site id.
     * @return array A dataset containing the nodes.
     */
    public function getSyncRoles($site) {
        if (is_numeric($site)) {
            $site = $this->getID($site);
        }

        // Get the roles.
        $roles = $this->SQL
            ->Select('RoleID', '', 'HubID')
            ->Select('Name')
            ->Select('Description')
            ->GetWhere('Role', ['HubSync' => ['settings', 'membership']])
            ->ResultArray();

        // Get the global permissions on the roles.
        $permissions = Gdn::PermissionModel()->GetGlobalPermissions(array_column($roles, 'HubID'));
        foreach ($roles as &$role) {
            $role['Permissions'] = $permissions[$role['HubID']];
            unset($role['Permissions']['PermissionID']);
        }

        $this->EventArguments['Multisite'] = $site;
        $this->EventArguments['Roles'] =& $roles;
        $this->FireEvent('getSyncRoles');

        return $roles;
    }

    public function getSyncCategories($site) {
        if (is_numeric($site)) {
            $site = $this->getID($site);
        }

        $categories = $this->SQL
            ->Select('CategoryID', '', 'HubID')
            ->Select('UrlCode,Name,Description')
            ->Select('ParentCategoryID', '', 'ParentHubID')
            ->OrderBy('TreeLeft')
            ->GetWhere('Category', ['CategoryID >' => 0, 'HubSync' => 'Settings'])
            ->ResultArray();
        $categories = array_column($categories, null, 'HubID');

        // Add a psuedo root category to get permissions.
        $categories['-1'] = [
            'HubID' => '-1',
            'Name' => 'Root'
        ];

        // Get the RoleIDs for permissions.
        $roles = $this->SQL
            ->Select('RoleID', '', 'HubID')
            ->GetWhere('Role', ['HubSync' => ['settings', 'membership']])
            ->ResultArray();
        $roleIDs = array_column($roles, 'HubID');

        // Get the default row for junction permissions.
        $defaultRow = $this->SQL->GetWhere('Permission', [
            'RoleID' => 0,
            'JunctionID' => null,
            'JunctionTable' => 'Category'
        ])->FirstRow(DATASET_TYPE_ARRAY);
        unset($defaultRow['PermissionID']);
        $defaultRow['RoleID'] = 2; // make sure in the array
        $defaultRow = array_Filter($defaultRow, function($v) {
            return in_array($v, [2, 3]);
        });

        // Now we need to grab the permissions for each role-category combination.
        $permissions = $this->SQL
            ->OrderBy('RoleID, JunctionID')
            ->GetWhere('Permission', [
                'RoleID' => $roleIDs,
                'JunctionID' => array_keys($categories)
            ])->ResultArray();

        // Walk through the permissions and nest them under the correct category.
        foreach ($permissions as $permissionRow) {
            $categoryID = $permissionRow['JunctionID'];
            $perms = array_intersect_key($permissionRow, $defaultRow);
            $categories[$categoryID]['Permissions'][] = $perms;
        }

        $this->EventArguments['Multisite'] = $site;
        $this->EventArguments['Categories'] =& $categories;
        $this->FireEvent('getSyncCategories');

        return $categories;
    }

    /**
     * Get a list of categories that are not supposed to sync with nodes.
     *
     * @param int|array $site The site we are looking at.
     * @return array|null
     */
    public function getDontSyncCategories($site) {
        $categories = $this->SQL
            ->Select('CategoryID', '', 'HubID')
            ->GetWhere('Category', ['CategoryID >' => 0, 'HubSync' => ''])
            ->ResultArray();
        $categories = array_column($categories, 'HubID');
        return $categories;
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

        $headers = [];

        // Kludge for osx that doesn't allow host files.
        $baseUrl = $this->siteUrl($node, true);
        $urlParts = parse_url($baseUrl);

        if ($urlParts['host'] === 'localhost' || StringEndsWith($urlParts['host'], '.lc')) {
            $headers['Host'] = $urlParts['host'];
            $urlParts['host'] = '127.0.0.1';
        }

        $url = rtrim(http_build_url($baseUrl, $urlParts), '/').'/'.ltrim($path, '/');

        if ($access_token = Infrastructure::clusterConfig('cluster.loader.apikey', '')) {
            $headers['Authorization'] = "token $access_token";
        }

        $request = new ProxyRequest();
        $response = $request->Request([
            'URL' => $url,
            'Method' => $method,
            'Cookies' => false,
            'Timeout' => 100,
        ], $params, null, $headers);

        if (strpos($request->ContentType, 'application/json') !== false) {
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
    public function saveAttribute($siteID, $attribute, $value = '') {
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

    public function syncNode($node, $url = '/utility/syncnode.json') {
        if (is_array($node)) {
            $nodeSlug = $node['Slug'];
        } else {
            $nodeSlug = $node;
        }

        try {
            $result = $this->nodeApi($nodeSlug, $url, 'POST');
        } catch (Exception $ex) {
            $result = ['Code' => $ex->getCode(), 'Exception' => $ex->getMessage()];
            LogException($ex);
        }
        return $result;
    }

    /**
     * Synchronize all of the categories that exist in one node.
     *
     * @param int $multiSiteID The ID of the node.
     * @param array[array] $nodeCategories A dataset of node category information.
     * @param bool $delete Whether or not to delete categories that no longer exist on the node.
     */
    public function syncNodeCategories($multiSiteID, $nodeCategories, $delete = true) {
        $now = Gdn_Format::toDateTime();

        // Get the current node categories.
        $currentCategories = $this->SQL->getWhere('NodeCategory', ['MultisiteID' => $multiSiteID])->resultArray();
        $currentCategories = Gdn_DataSet::index($currentCategories, 'CategoryID');
        $result = ['Inserted' => 0, 'Updated' => 0];

        foreach ($nodeCategories as $category) {
            $categoryID = $category['CategoryID'];
            $set = arrayTranslate($category, ['Name', 'UrlCode', 'HubID']);
            $set['DateLastSync'] = $now;

            if (array_key_exists($category['CategoryID'], $currentCategories)) {
                $this->SQL->put(
                    'NodeCategory',
                    $set,
                    ['NodeCategoryID' => $currentCategories[$categoryID]['NodeCategoryID']]
                );
                $result['Updated']++;
            } else {
                $set['MultisiteID'] = $multiSiteID;
                $set['CategoryID'] = $categoryID;
                $this->SQL->insert(
                    'NodeCategory',
                    $set
                );
                $result['Inserted']++;
            }
        }

        if ($delete) {
            // Delete all of the node categories that no longer exist.
            $this->SQL->delete('NodeCategory', ['MultisiteID' => $multiSiteID, 'DateLastSync <' => $now]);
            $d = $this->Database;
            $result['Deleted'] = val('RowCount', $this->Database->LastInfo, 0);

        }

        return $result;
    }

    public function syncNodes() {
        $urls = [
            '/utility/syncnode.json',
        ];

        $this->EventArguments['urls'] =& $urls;
        $this->FireEvent('SyncNodes');

        list($match) = explode('.', $this->siteNameFormat, 2);
        $match = sprintf($match, '*');

        if (class_exists('Communication') && class_exists('Infrastructure')) {
            foreach ($urls as $url) {
                $query = Communication::data('/forum/callback')
                    ->method('post')
                    ->parameter('method', 'POST')
                    ->parameter('secure', (bool)C('Garden.ForceSSL'))
                    ->parameter('path', $url)
                    ->parameter('match', $match)
                    ->parameter('headers', [
                        'Authorization' => self::apikey(true)
                    ])
                    ->parameter('accountid', Infrastructure::site('accountid'));

                $result[$url] = $query->send();
            }
            return $result;
        }

        $result = [];
        $nodes = $this->get()->resultArray();

        foreach ($nodes as $node) {
            if (!val('Sync', $node, 1)) {
                continue;
            }

            foreach ($urls as $url) {
                try {
                    $nodeResult = $this->syncNode($node, $url);
                    $result[$node['Slug']][$url] = $nodeResult;
                } catch (Exception $ex) {
                    $result[$node['Slug']][$url] = (string)$ex->getCode();
                }
            }
        }

        return $result;
    }
}

function validate_slug($value) {
    if (!ValidateRequired($value)) {
        return true;
    }
    return preg_match('`^[a-z0-9-]+$`', $value);
}
