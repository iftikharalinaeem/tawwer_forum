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

        $this->Validation->addRule('Slug', 'func:validate_slug');
        $this->Validation->applyRule('Slug', 'Slug', 'The slug must consist of numbers, lowercase letters or a slash.');

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
     * Get all of the node sites for a locale.
     *
     * The node sites consist of the following:
     *
     * - All of the nodes.
     * - All of the subcommunities on nodes with subcommunities enabled.
     *
     * If a node has subcommunities enabled then its main site is not returned, just its subcommunities.
     *
     * @param string $locale The locale to get the sites for.
     * @return array Returns an array of sites.
     */
    public function getNodeSites($locale) {
        // Get all of the nodes.
        $nodes = $this->getWhere(['Locale' => $locale])->resultArray();
        $nodes = array_column($nodes, null, 'MultisiteID');

        // Get all of the subcommunities.
        $subcommunities = $this->SQL->getWhere('NodeSubcommunity', ['Locale' => $locale])->resultArray();

        // Combine the nodes and subcommunities.
        $result = [];
        $remainingNodes = $nodes;
        foreach ($subcommunities as $subcommunity) {
            $node = $nodes[$subcommunity['MultisiteID']];

            $result[] = [
                'Name' => $subcommunity['Name'],
                'Url' => $node['FullUrl'].'/'.$subcommunity['Folder'],
                'Locale' => $subcommunity['Locale'],
                'MultisiteID' => $subcommunity['MultisiteID'],
                'Type' => 'subcommunity'
            ];

            // This node has subcommunities so can be taken out of circulation.
            unset($remainingNodes[$node['MultisiteID']]);
        }

        // Add the remaining nodes.
        foreach ($remainingNodes as $node) {
            $result[] = [
                'Name' => $node['Name'],
                'Url' => $node['FullUrl'],
                'Locale' => $node['Locale'],
                'MultisiteID' => $node['MultisiteID'],
                'Type' => 'node'
            ];
        }

        // Sort the results by name.
        usort($result, function ($a, $b) {
            return strcasecmp($a['Name'], $b['Name']);
        });

        return $result;
    }

    /**
     * Get the hub slug that is prepended to every node slug when a node is created.
     */
    public function getHubSlug() {
        $nameFormat = sprintf($this->siteNameFormat, '%s'); // in case the format uses fancy %s

        $slug = rtrim(strstr($nameFormat, '%s', true), '-');
        return $slug;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($where = [], $options = []) {
        if (!empty($where[$this->PrimaryKey])) {
            $ids = (array)$where[$this->PrimaryKey];
        } else {
            $rows = $this->getWhere($where)->resultArray();
            $ids = array_column($rows, $this->PrimaryKey);
        }

        $r = parent::delete($where, $options);

        // Delete the NodeCategories too.
        $this->SQL->delete('NodeCategory', [$this->PrimaryKey => $ids]);
        // Delete the subcommunities.
        $this->SQL->delete('NodeSubcommunity', [$this->PrimaryKey => $ids]);

        return $r;
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
            throw notFoundException('Site');
        }

        $valid = true;

        if (!in_array($site['Status'], ['active', 'error', 'building', 'deleting'])) {
            $this->Validation->addValidationResult('Status', 'Cannot delete a site that isn\'t active.');
            $valid = false;
        }

        if (!$valid) {
            return false;
        }

        $deleteQuery = Communication::orchestration('/site/delete')
            ->method('post')
            ->parameter('siteid', val('SiteID', $site))
            ->parameter('callback', [
                'url' => Gdn::request()->domain()."/hub/api/v1/multisites/{$site['MultisiteID']}/deletecallback.json",
                'headers' => [
                    'Authorization' => self::apikey(true)
                ]
            ]);
        $deleteResponse = $deleteQuery->send();

        if ($deleteQuery->responseClass('2xx')) {
            // The site built.
            $this->setField($id, [
                'Status' => 'deleting',
                'DateStatus' => Gdn_Format::toDateTime(),
                'SiteID' => valr('site.SiteID', $deleteResponse)
            ]);
            return true;
        } elseif (valr('site.state', $deleteResponse) === 'deleted') {
            // The site was already deleted.
            return $this->delete([$this->PrimaryKey => $id]);
        } else {
            // The site has an error.
            $error = $deleteQuery->errorMsg();
            $this->Validation->addValidationResult('MultisiteID', '@'.$error);
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
            $this->Validation->addValidationResult('Slug', "The slug '$slug' is already in use by another site.");
            return false;
        }
        if ($this->getWhere(['Slug' => $slug])->numRows()) {
            $this->Validation->addValidationResult('Slug', "The slug '$slug' is already in use by another site.");
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
            throw notFoundException('Site');
        }

        $valid = true;

        if (!in_array($site['Status'], ['pending', 'error'])) {
            $this->Validation->addValidationResult('Status', 'Cannot build a site that isn\'t pending.');
            $valid = false;
        }

        if (!$valid) {
            return false;
        }

        $name = sprintf($this->siteNameFormat, $site['Slug']);

        $config = [
            'Garden' => [
                'Email' => [
                    'SupportAddress' => strtolower($site['Slug']).'.'.$this->getHubSlug().'@vanillaforums.email'
                ]
            ]
        ];

        $buildQuery = Communication::orchestration('/site/createnode')
            ->method('post')
            ->parameter('name', $name)
            ->parameter('accountid', Infrastructure::site('accountid'))
            ->parameter('domain', Gdn::request()->host()) // TODO: make work for non-dirs
            ->parameter('flavor', 'node')
            ->parameter('config', $config)
            ->parameter('callback', [
                'url' => Gdn::request()->domain()."/hub/api/v1/multisites/{$site['MultisiteID']}/buildcallback.json",
                'headers' => [
                    'Authorization' => self::apikey(true)
                ]
            ]);
        $build = $buildQuery->send();

        if ($buildQuery->responseClass('2xx')) {
            // The site built.
            $this->setField($id, [
                'Status' => 'building',
                'DateStatus' => Gdn_Format::toDateTime(),
                'SiteID' => valr('site.SiteID', $build)
            ]);
            return true;
        } elseif ($buildQuery->code() == 409 && val('site', $build)) {
            // The site already existed.
            $this->status($id, 'active');
            $siteID = valr('site.SiteID', $build);
            if ($siteID) {
                $this->setField($id, 'SiteID', $siteID);
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
            'DateStatus' => Gdn_Format::toDateTime()
        ];

        if ($error) {
            $attributes['Error'] = $error;
            $set['Attributes'] = $attributes;
        } elseif (isset($site['Attributes']['Error'])) {
            unset($attributes['Error']);
            $set['Attributes'] = $attributes;
        }

        $this->setField($id, $set);
    }

    public function insert($fields) {
        $result = true;

        // Sites can only be inserted in the pending status.
        $fields['Status'] = 'pending';
        $fields['DateStatus'] = Gdn_Format::toDateTime();

        $slug = val('Slug', $fields);

        if ($slug) {
            touchValue('Name', $fields, sprintf($this->siteNameFormat, $slug));
            touchValue('Url', $fields, $this->siteUrl($slug, false));

            // Check to see if the slug already exists.
            $result = $this->validateSlugUnique($slug);
        }

        if ($result) {
            $result = parent::insert($fields);
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

        return parent::update($fields, $where, $limit);
    }

    public function calculateRow(&$row) {
        $url = $row['Url'];
        if (isUrl($url)) {
            $row['FullUrl'] = $url;
        } else {
            $row['FullUrl'] = Gdn::request()->domain().$url;
        }

        if (isset($row['Attributes']) && is_string($row['Attributes'])) {
            $row['Attributes'] = @unserialize($row['Attributes']);
        }
        if (!isset($row['Attributes']) || !is_array($row['Attributes'])) {
            $row['Attributes'] = [];
        }
    }

    public function getID($id, $datasetTye = false, $options = []) {
        $row = parent::getID($id, DATASET_TYPE_ARRAY);
        if ($row) {
            $this->calculateRow($row);
        }
        return $row;
    }

    /**
     * Get all of the locales used on the nodes.
     *
     * @return array Returns a locales dataset.
     */
    public function getNodeLocales() {
        // Get all of the locales from the nodes.
        $nodeLocales = $this->SQL
            ->select('Locale')
            ->from('Multisite')
            ->groupBy('Locale')
            ->get()
            ->resultArray();
        $locales = array_column($nodeLocales, 'Locale', 'Locale');

        $subcommunityLocales = $this->SQL
            ->select('Locale')
            ->from('NodeSubcommunity')
            ->groupBy('Locale')
            ->get()
            ->resultArray();
        $locales = array_replace($locales, array_column($subcommunityLocales, 'Locale', 'Locale'));
        $locales = array_filter($locales);

        // Sort the array, keeping English up top.
        usort($locales, function ($a, $b) {
            if ($a === $b) {
                return 0;
            } elseif ($a === 'en') {
                return -1;
            } elseif ($b === 'en') {
                return 1;
            } else {
                return strcasecmp($a, $b);
            }
        });

        $exceptions = [
            'ru__PETR1708' => ['Locale' => 'ru', 'Name' => 'русский', 'Language' => 'русский'],

        ];

        // Add some more information to the locales.
        $result = [];
        foreach ($locales as $locale) {
            $code = str_replace('_', '-', $locale);
            $locale = Gdn_Locale::canonicalize($locale);

            if (class_exists('Locale')) {
                $row = [
                    'Locale' => $code,
                    'Name' => self::mb_ucfirst(Locale::getDisplayName($locale, $locale)),
                    'Language' => self::mb_ucfirst(Locale::getDisplayLanguage($locale, $locale)),
                    'Url' => url("/categories/sites/$code", '/')
                ];

                if (isset($exceptions[$locale])) {
                    $row = array_replace($row, $exceptions[$locale]);
                }
            } else {
                $row = [
                    'Locale' => $code,
                    'Name' => $code,
                    'Language' => $code,
                    'Url' => url("/categories/$code")
                ];
            }
            $result[$locale] = $row;
        }

        return $result;
    }

    private static function mb_ucfirst($str, $encoding = "UTF-8", $lower_str_end = false) {
        $first_letter = mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding);
        $str_end = "";
        if ($lower_str_end) {
            $str_end = mb_strtolower(mb_substr($str, 1, mb_strlen($str, $encoding), $encoding), $encoding);
        } else {
            $str_end = mb_substr($str, 1, mb_strlen($str, $encoding), $encoding);
        }
        $str = $first_letter . $str_end;
        return $str;
    }

    public function getFromUrl($url) {
        $slug = $this->slugFromUrl($url);
        $row = $this->getWhere(['Slug' => $slug])->firstRow(DATASET_TYPE_ARRAY);
        return $row;
    }

    public function slugFromUrl($url) {
        $format = $this->siteUrlFormat;

        if (!isUrl($format)) {
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
            $rows = parent::get($orderFields, $orderDirection, $limit, pageNumber($offset, $limit));
        } else {
            $rows = parent::getWhere($where, $orderFields, $orderDirection, $limit, $offset);
        }
        array_walk($rows->resultArray(), [$this, 'calculateRow']);
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
            ->getWhere('Role', ['HubSync' => ['settings', 'membership']])
            ->resultArray();

        foreach ($roles as &$role) {
            $role['HubID'] = $role['RoleID'];
            unset($role['RoleID'], $role['HubSettings'], $role['HubMembership'], $role['HubSync']);
        }

        // Get the global permissions on the roles.
        $permissions = Gdn::permissionModel()->getGlobalPermissions(array_column($roles, 'HubID'));
        foreach ($roles as &$role) {
            $role['Permissions'] = $permissions[$role['HubID']];
            unset($role['Permissions']['PermissionID']);
        }

        $this->EventArguments['Multisite'] = $site;
        $this->EventArguments['Roles'] =& $roles;
        $this->fireEvent('getSyncRoles');

        return $roles;
    }

    public function getSyncCategories($site) {
        if (is_numeric($site)) {
            $site = $this->getID($site);
        }

        $categories = $this->SQL
            ->select('CategoryID', '', 'HubID')
            ->select('UrlCode,Name,Description')
            ->select('ParentCategoryID', '', 'ParentHubID')
            ->orderBy('TreeLeft')
            ->getWhere('Category', ['CategoryID >' => 0, 'HubSync' => 'Settings'])
            ->resultArray();
        $categories = array_column($categories, null, 'HubID');

        // Add a psuedo root category to get permissions.
        $categories['-1'] = [
            'HubID' => '-1',
            'Name' => 'Root'
        ];

        // Get the RoleIDs for permissions.
        $roles = $this->SQL
            ->select('RoleID', '', 'HubID')
            ->getWhere('Role', ['HubSync' => ['settings', 'membership']])
            ->resultArray();
        $roleIDs = array_column($roles, 'HubID');

        // Get the default row for junction permissions.
        $defaultRow = $this->SQL->getWhere('Permission', [
            'RoleID' => 0,
            'JunctionID' => null,
            'JunctionTable' => 'Category'
        ])->firstRow(DATASET_TYPE_ARRAY);
        unset($defaultRow['PermissionID']);
        $defaultRow['RoleID'] = 2; // make sure in the array
        $defaultRow = array_filter($defaultRow, function($v) {
            return in_array($v, [2, 3]);
        });

        // Now we need to grab the permissions for each role-category combination.
        $permissions = $this->SQL
            ->orderBy('RoleID, JunctionID')
            ->getWhere('Permission', [
                'RoleID' => $roleIDs,
                'JunctionID' => array_keys($categories)
            ])->resultArray();

        // Walk through the permissions and nest them under the correct category.
        foreach ($permissions as $permissionRow) {
            $categoryID = $permissionRow['JunctionID'];
            $perms = array_intersect_key($permissionRow, $defaultRow);
            $categories[$categoryID]['Permissions'][] = $perms;
        }

        $this->EventArguments['Multisite'] = $site;
        $this->EventArguments['Categories'] =& $categories;
        $this->fireEvent('getSyncCategories');

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
            ->select('CategoryID', '', 'HubID')
            ->getWhere('Category', ['CategoryID >' => 0, 'HubSync' => ''])
            ->resultArray();
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
        $response = $request->request([
            'URL' => $url,
            'Method' => $method,
            'Cookies' => false,
            'Timeout' => 100,
        ], $params, null, $headers);

        if (strpos($request->ContentType, 'application/json') !== false) {
            $response = json_decode($response, true);
        }

        if ($request->ResponseStatus != 200) {
            trace($response, "Error {$request->ResponseStatus}");
            throw new Gdn_UserException('api: '.val('Exception', $response, 'There was an error performing your request.'), $request->ResponseStatus);
        }

        trace($response, "hub api response");
        return $response;
    }

    /**
     * Saves a site attribute to the database.
     *
     * This is a convenience method that uses $this->SaveToSerializedColumn().
     *
     * @param int $siteID The site ID to save.
     * @param string|array $attribute The name of the attribute being saved, or an associative array of name => value pairs to be saved.
     * If this is an associative array, the $Value argument will be ignored.
     * @param mixed $value The value being saved.
     * @return bool Whether or not the attribute as saved.
     */
    public function saveAttribute($siteID, $attribute, $value = '') {
        return $this->saveToSerializedColumn('Attributes', $siteID, $attribute, $value);
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
            ->beginWhereGroup()
            ->orLike('Name', $search)
            ->orLike('Url', $search)
            ->endWhereGroup();

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
            logException($ex);
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

    /**
     * Synchronize all of the subcommunities that exist in one node.
     *
     * @param int $multiSiteID The ID of the node.
     * @param array[array] $nodeSubcommunities A dataset of node subcommunity information.
     * @param bool $delete Whether or not to delete subcommunities that no longer exist on the node.
     */
    public function syncNodeSubcommunities($multiSiteID, $nodeSubcommunities, $delete = true) {
        $now = Gdn_Format::toDateTime();

        // Get the current node subcommunity.
        $currentSubcommunities = $this->SQL->getWhere('NodeSubcommunity', ['MultisiteID' => $multiSiteID])->resultArray();
        $currentSubcommunities = array_column($currentSubcommunities, null, 'SubcommunityID');
        $result = ['Inserted' => 0, 'Updated' => 0];

        foreach ($nodeSubcommunities as $subcommunity) {
            $subcommunityID = $subcommunity['SubcommunityID'];
            $set = arrayTranslate($subcommunity, ['Name', 'Folder', 'CategoryID', 'Locale', 'IsDefault']);
            $set['DateLastSync'] = $now;

            if (array_key_exists($subcommunityID, $currentSubcommunities)) {
                $this->SQL->put(
                    'NodeSubcommunity',
                    $set,
                    ['NodeSubcommunityID' => $currentSubcommunities[$subcommunityID]['NodeSubcommunityID']]
                );
                $result['Updated']++;
            } else {
                $set['MultisiteID'] = $multiSiteID;
                $set['SubcommunityID'] = $subcommunityID;
                $this->SQL->insert(
                    'NodeSubcommunity',
                    $set
                );
                $result['Inserted']++;
            }
        }

        if ($delete) {
            // Delete all of the node subcommunities that no longer exist.
            $this->SQL->delete('NodeSubcommunity', ['MultisiteID' => $multiSiteID, 'DateLastSync <' => $now]);
            $d = $this->Database;
            $result['Deleted'] = val('RowCount', $d->LastInfo, 0);

        }

        return $result;
    }

    public function syncNodes() {
        $urls = [
            '/utility/syncnode.json',
        ];

        $this->EventArguments['urls'] =& $urls;
        $this->fireEvent('SyncNodes');

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
                    ->parameter('accountid', Infrastructure::site('accountid'))
                    ->requestOption('LogResponseBody', false);

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
    if (!validateRequired($value)) {
        return true;
    }
    return preg_match('`^[a-z0-9-]+$`', $value);
}


/**
 *
 *
 * Filter down categories to current node or hub.
 *
 * @param DiscussionsController $sender Sending controller instance.
 * @param array $args Event arguments.
 */
public function sitemapsPlugin_siteMapCategories_handler($sender, $args) {
    if (!SubCommunityModel::getCurrent()) {
        return;
    }

    $subcommunityCategoryIDs = $this->getCategoryIDs();

    $args['Categories'] = array_intersect_key($args['Categories'], array_flip($subcommunityCategoryIDs));
}
