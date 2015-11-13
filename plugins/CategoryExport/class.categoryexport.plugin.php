<?php

/**
 * @copyright 2010-2015 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 */

$PluginInfo['CategoryExport'] = [
    'Name' => 'Category Export',
    'Description' => "Export discussions for given category.",
    'Version' => '1.0.0',
    'RequiredApplications' => ['Vanilla' => '2.2'],
    'RequiredTheme' => false,
    'RequiredPlugins' => false,
    'HasLocale' => false,
    'SettingsUrl' => '/categoryexport',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'Author' => "Tim Gunter",
    'AuthorEmail' => 'tim@vanillaforums.com',
    'AuthorUrl' => 'https://vanillaforums.com'
];

/**
 * Category Export
 * 
 * Export a category's content in a downloadable format.
 * 
 * Configuration Keys:
 *   Plugins.CategoryExport.MaxRootRows
 *   Plugins.CategoryExport.MaxChildRows
 *   Plugins.CategoryExport.Cooldown
 *   Plugins.CategoryExport.CategoryCooldown
 * 
 * This plugin uses memcached to keep track of export cooldowns. Clearing the 
 * cache will reset these.
 * 
 * @package internal
 * @author Tim Gunter <tim@vanillaforums.com>
 */
class CategoryExportPlugin extends Gdn_Plugin  {

    /**
     * Max root rows (discussions)
     * Controllable by Plugins.CategoryExport.MaxRootRows
     */
    const DEFAULT_MAX_ROOT = 300;
    
    /**
     * Max child rows (comments)
     * Controllable by Plugins.CategoryExport.MaxChildRows
     */
    const DEFAULT_MAX_CHILD = 500;
    const ROOT_CHUNKS = 100;
    const EACH_CHUNKS = 100;
    
    /**
     * Global export cooldown
     * Controllable by Plugins.CategoryExport.Cooldown
     */
    const DEFAULT_GLOBAL_COOLDOWN = 2; // hours
    /**
     * Per category export cooldown
     * Controllable by Plugins.CategoryExport.CategoryCooldown
     */
    const DEFAULT_CATEGORY_COOLDOWN = 12; // hours
    
    const COOLDOWN_GLOBAL_KEY = 'plugins.categoryexport.cooldown';
    const COOLDOWN_CATEGORY_KEY = 'plugins.categoryexport.cooldown.%d';
    const EXPORT_DATE = 'plugins.categoryexport.lastdate';
    
    const FORMAT_CSV = 'csv';
    const FORMAT_JSON = 'json';

    protected static $fieldSeparator = ',';
    protected static $lineSeparator = "\r\n";
    protected static $wrapCharacter = '"';
    protected static $escapeCharacter = '\\';

    /**
     * Export inclusion receipes
     * @var array
     */
    protected static $includes = [
        'discussions' => [
            'priority' => 1,
            'model' => 'DiscussionModel',
            'table' => 'Discussion d',
            'for' => 'root', // special value, this is the root recipe
            'key' => 'd.CategoryID',
            'fields' => [
                'd.DiscussionID',
                'd.DateInserted as DiscussionDate',
                'd.Name as DiscussionName',
                'd.InsertUserID as DiscussionUserID',
                'du.Name as DiscussionUserName',
                'du.Email as DiscussionUserEmail'
            ],
            'join' => [
                [
                    'table' => 'User du',
                    'on' => ['d.InsertUserID', 'du.UserID']
                ]
            ],
            'cache' => 'discussions', // cache as 'discussions'
            'output' => true
        ],
        'comments' => [
            'priority' => 2,
            'model' => 'CommentModel',
            'table' => 'Comment c',
            'for' => 'discussions', // for each item in 'discussions' cache
            'includesource' => true,
            'key' => 'c.DiscussionID',
            'keyval' => 'DiscussionID',
            'fields' => [
                'c.CommentID',
                'c.DateInserted as CommentDate',
                'c.Body',
                'c.InsertUserID as CommentUserID',
                'cu.Name as CommenterName',
                'cu.Email as CommenterEmail'
            ],
            'join' => [
                [
                    'table' => 'User cu',
                    'on' => ['c.InsertUserID','cu.UserID']
                ]
            ],
            'output' => true
        ]
    ];

    /**
     * Add sidemenu
     *
     * @param Gdn_Controller $sender
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        $menu = $sender->EventArguments['SideMenu'];
        $menu->addLink('Dashboard', T('Category Export'), '/categoryexport', 'Garden.Settings.Manage');
    }
    
    /**
     * Check if we can export this category
     * 
     * @param integer $categoryID optional. check this category's cooldown as well.
     * @return boolean
     */
    public static function canExport($categoryID = null) {
        $globalKey = self::COOLDOWN_GLOBAL_KEY;
        if ((boolean)Gdn::cache()->get($globalKey)) {
            return false;
        }
        
        if ($categoryID) {
            $categoryKey = sprintf(self::COOLDOWN_CATEGORY_KEY, $categoryID);
            if ((boolean)Gdn::cache()->get($categoryKey)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get number of minutes to wait until export is possible
     * 
     * @param integer $categoryID optional. check this category's cooldown as well.
     * @return integer minutes
     */
    public static function getCooldownWait($categoryID = null) {
        $cooldown = 0;
        
        // Check the global cooldown
        $globalKey = self::COOLDOWN_GLOBAL_KEY;
        $globalCooldown = Gdn::cache()->get($globalKey);
        if ($globalCooldown) {
            $cooldown = $globalCooldown;
        }
        
        // If a category id was supplied, check its cooldown
        if ($categoryID) {
            $categoryKey = sprintf(self::COOLDOWN_CATEGORY_KEY, $categoryID);
            $categoryCooldown = Gdn::cache()->get($categoryKey);
            if ($categoryCooldown && $categoryCooldown > $cooldown) {
                $cooldown = $categoryCooldown;
            }
        }
        
        // Return the cooldown in minutes
        if ($cooldown > time()) {
            return (($cooldown - time()) / 60);
        } else {
            return 0;
        }
    }
    
    /**
     * Get last export date
     * 
     * Return the date the last export was performed.
     * 
     * @return string
     */
    public static function getLastExportDate() {
        $exported = Gdn::cache()->get(self::EXPORT_DATE);
        return $exported ? $exported : 'unknown';
    }
    
    /**
     * Set exported keys
     * 
     * This method writes memcached keys to keep track of export cooldowns globally 
     * and per category. Each key contains the timestamp when the key will expire,
     * and the keys self-expire at that time.
     * 
     * @param integer $categoryID
     */
    public static function setExported($categoryID) {
        // Set global cooldown
        $globalCooldown = c('Plugins.CategoryExport.Cooldown', CategoryExportPlugin::DEFAULT_GLOBAL_COOLDOWN);
        $globalCooldownExpiry = strtotime(sprintf('+%d hours', $globalCooldown));
        $globalCooldownTime = $globalCooldown * 3600;
        Gdn::cache()->store(self::COOLDOWN_GLOBAL_KEY, $globalCooldownExpiry, [
            Gdn_Cache::FEATURE_EXPIRY => $globalCooldownTime
        ]);
        
        // Set category cooldown
        $categoryCooldown = c('Plugins.CategoryExport.CategoryCooldown', CategoryExportPlugin::DEFAULT_CATEGORY_COOLDOWN);
        $categoryCooldownExpiry = strtotime(sprintf('+%s hours', $categoryCooldown));
        $categorykey = sprintf(self::COOLDOWN_CATEGORY_KEY, $categoryID);
        $categoryCooldownTime = $categoryCooldown * 3600;
        Gdn::cache()->store($categorykey, $categoryCooldownExpiry, [
            Gdn_Cache::FEATURE_EXPIRY => $categoryCooldownTime
        ]);
        
        // Set export date
        Gdn::cache()->store(self::EXPORT_DATE, date('Y-m-d H:i:s'));
    }
    
    /**
     * Download category export
     * 
     * Allow downloading of category export payload. 
     * 
     * @param integer $categoryID the category to export
     * @param array $include list of items to include
     * @param array $options optional. contains the format of the export. default is self::FORMAT_CSV
     */
    public static function export($categoryID, $include, $options) {
        
        // Check permission
        if (!Gdn::session()->checkPermission('Garden.Settings.Manage')) {
            throw permissionException('Garden.Settings.Manage');
        }
        
        // Check category
        $category = CategoryModel::categories($categoryID);
        if (!$category) {
            throw new Exception('No such category.');
        }
        
        // Check export cooldown
        if (!self::canExport($categoryID)) {
            $waitTime = self::getCooldownWait($categoryID);
            $delay = Gdn_Format::seconds($waitTime * 60);
            throw new Exception(sprintf(t("Category '%s' was exported too recently. Please wait <b>%s</b> and try again."), $category['Name'], $delay));
        }
        
        // Make sure we have 'discussions' as an included source always
        if (!in_array('discussions', $include)) {
            array_unshift($include, 'discussions');
        }
        
        $maxRootRows = c('Plugins.CategoryExport.MaxRootRows', self::DEFAULT_MAX_ROOT);
        $maxChildRows = c('Plugins.CategoryExport.MaxChildRows', self::DEFAULT_MAX_CHILD);
        
        $cache = [];
        $headers = [];
        
        // Determine which recipe to actually output
        $recipes = [];
        $sendOutput = null;
        $outputPriority = 0;
        
        foreach ($include as $recipeName) {
            $recipe = val($recipeName, self::$includes, false);
            if (!$recipe) {
                throw new Exception(sprintf(t("Requested include '%s' is unknown. Please choose from the list."), $recipeName));
            }
            $recipePriority = val('priority', $recipe, 10);
            
            if (val('output', $recipe, false)) {
                if ($recipePriority > $outputPriority) {
                    $sendOutput = $recipeName;
                    $outputPriority = $recipePriority;
                }
            }
            
            $recipes[$recipeName] = $recipePriority;
        }
        asort($recipes);

        $data = [];
        foreach ($recipes as $recipeName => $priority) {
            $recipe = val($recipeName, self::$includes);
            
            // Determine if we're outputting this data
            $sending = ($recipeName == $sendOutput);
            
            $modelName = val("model", $recipe);
            
            /* @var $model Gdn_Model */
            $model = new $modelName; 
            $rawFields = val("fields", $recipe);
            $headers = array_merge($headers, $rawFields);
            $fields = implode(',', $rawFields);
            
            $dataQuery = $model->SQL->select($fields)
                ->from(val('table', $recipe));
            
            // Joins
            $joins = val('join', $recipe, []);
            foreach ($joins as $join) {
                $dataQuery->join($join['table'], "{$join['on'][0]} = {$join['on'][1]}");
            }
            
            $data = [];
            
            // If we're the root item, key off category ID
            $for = val('for', $recipe);
            if ($for == 'root') {
                                
                $dataQuery->where([
                    $recipe['key'] => $categoryID
                ]);
                
                // Get data in chunks
                $i = 0;
                for ($i = 0; $i < $maxRootRows; $i += self::ROOT_CHUNKS) {
                    $chunkQuery = clone $dataQuery;
                    $dataQuery->limit(self::ROOT_CHUNKS, $i);
                    $rows = $dataQuery->get()->resultArray();
                    $data = array_merge($data, $rows);
                    
                    if (count($rows) < self::ROOT_CHUNKS) {
                        break;
                    }
                }
                
            } else {
                                
                // Iterate over source rows
                foreach ($cache[$for] as $source) {
                    $keyVal = val($recipe['keyval'], $source);
                    
                    // Get data in chunks
                    for ($i = 0; $i < $maxChildRows; $i += self::EACH_CHUNKS) {
                        $chunkQuery = clone $dataQuery;
                        $chunkQuery->where([
                                $recipe['key'] => $keyVal
                            ])
                            ->limit(self::EACH_CHUNKS, $i);

                        $rows = $chunkQuery->get()->resultArray();
                        
                        // Attach source row if required
                        if (val('includesource', $recipe, false) && $sending) {
                            foreach ($rows as &$row) {
                                $row = array_merge($source, $row);
                            }
                        }
                        
                        $data = array_merge($data, $rows);
                        if (count($rows) < self::EACH_CHUNKS) {
                            break;
                        }
                    }
                }
            }
            
            // Cache if not final output, and caching enabled
            $cacheAs = val('cache', $recipe);
            if ($cacheAs && $recipeName != $sendOutput) {
                $cache[$cacheAs] = $data;
            }
            
            // Output
            if ($recipeName == $sendOutput) {
                break;
            }
        }
        
        $format = val('format', $options, self::FORMAT_CSV);
        
        // Fix headers
        $fields = [];
        foreach ($headers as $header) {
            $work = explode('.', $header);
            if (count($work) > 1) {
                $header = $work[1];
            }

            $work = explode(' ', $header);
            if (count($work) > 1) {
                $header = array_pop($work);
            }

            $fields[] = $header;
        }

        // Log this export
        self::setExported($categoryID);

        Logger::event(
            'category_export',
            Logger::INFO,
            '{username} exported category {categoryid} ({categoryname}) as {format}.',
            [
                'username' => Gdn::session()->User->Name,
                'categoryid' => $category['CategoryID'],
                'categoryname' => $category['Name'],
                'format' => $format
            ]
        );
        
        switch ($format) {
            case self::FORMAT_CSV:
                
                // Start buffering output
                ob_start();
                
                // Send data
                self::writeCSV($fields, $data);
                
                $contentLength = strlen(ob_get_contents());
                
                // Send CSV headers
                $exportFile = "export-category-{$categoryID}.csv";
                safeHeader("Content-Type: text/csv");
                safeHeader("Content-Disposition: attachment; filename=\"{$exportFile}\"");
                safeHeader("Content-Length: {$contentLength}");
                
                // Send output
                ob_end_flush();
                flush();
                
                break;
            
            case self::FORMAT_JSON:
                
                // Start buffering output
                ob_start();
                
                // Send data
                self::writeJSON($fields, $data);
                
                $contentLength = strlen(ob_get_contents());
                
                // Send CSV headers
                $exportFile = "export-category-{$categoryID}.json";
                safeHeader("Content-Type: application/json");
                safeHeader("Content-Disposition: attachment; filename=\"{$exportFile}\"");
                safeHeader("Content-Length: {$contentLength}");
                
                // Send output
                ob_end_flush();
                flush();
                break;
            
            default:
                throw new Exception(sprintf(t("Unsupported export format '%s'."), $format));
        }

        exit();
    }

    /**
     * Quote a CSV value
     * 
     * @param string $field
     * @return true
     */
    private static function quoteCSV(&$field) {
        $field = addslashes(trim($field));
        $field = self::$wrapCharacter.$field.self::$wrapCharacter;
        return true;
    }
    
    /**
     * Write CSV data to output
     * 
     * @param array $fields
     * @param array $data
     */
    private static function writeCSV($fields, $data) {
        if (empty($fields) || !is_array($fields)) {
            return false;
        }
        
        // Send headers
        $headers = $fields;
        array_walk($headers, ['self', 'quoteCSV']);
        echo implode(self::$fieldSeparator, $headers).self::$lineSeparator;
        
        // Iterate over data and write to output
        foreach ($data as $item) {
            $line = [];
            array_walk($item, ['self', 'quoteCSV']);
            foreach ($fields as $field) {
                $line[] = val($field, $item, '');
            }
            echo implode(self::$fieldSeparator, $line).self::$lineSeparator;
        }
    }
    
    /**
     * Write JSON data to output
     * 
     * @param array $fields
     * @param array $data
     */
    private static function writeJSON($fields, $data) {
        if (empty($fields) || !is_array($fields)) {
            return false;
        }
        
        echo json_encode([
            'fields' => $fields,
            'data' => $data
        ]);
    }

    /**
     * On-enable setup
     */
    public function setup() {
        // Nothing
    }
    
    /**
     * On-update structure
     */
    public function structure() {
        // Nothing
    }
    
}
