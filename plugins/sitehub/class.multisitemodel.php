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
            ->parameter('cluster', Infrastructure::cluster())
//            ->parameter('planid', $plan['FeatureID'])
//            ->parameter('adminname', Gdn::Session()->User->Name)
//            ->parameter('adminpassword', BetterRandomString(16, 'Aa0'))
//            ->parameter('adminemail', Gdn::Session()->User->Email)
            ->parameter('callbackurl', Gdn::Request()->Domain()."/hub/multisites/{$site['MultisiteID']}/buildcallback?access_token=".C('Plugins.SimpleAPI.AccessToken'));
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
        $rows = parent::GetWhere($where, $orderFields, $orderDirection, $limit, $offset);
        array_walk($rows->ResultArray(), [$this, 'calculateRow']);
        return $rows;
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
}