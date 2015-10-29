<?php if (!defined('APPLICATION')) exit();

// Define the plugin:
$PluginInfo['CategoryExport'] = array(
    'Name' => 'Category Export',
    'Description' => "Export discussions for given category.",
    'Version' => '1.0.0',
    'RequiredApplications' => array('Vanilla' => '2.2'),
    'RequiredTheme' => false,
    'RequiredPlugins' => false,
    'HasLocale' => false,
    'SettingsUrl' => '/plugin/categoryexport',
    'SettingsPermission' => 'Garden.AdminUser.Only',
    'Author' => "Deric D. Davis",
    'AuthorEmail' => 'deric.d@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.com'
);

class CategoryExport extends Gdn_Plugin  {

    const DEFAULT_MAX = 2000;

    protected $fieldSep   = ',';
    protected $lineSep    = "\r\n";
    protected $escapeChar = '"';

    protected $exportDiscussionFields = array(
        'd.DiscussionID',
        'd.CategoryID',
        'd.InsertUserID',
        'u.Email',
        'd.UpdateUserID',
        'd.FirstCommentID',
        'd.LastCommentID',
        'd.Name',
        'd.Body',
        'd.Format',
        'd.Tags',
        'd.CountComments',
        'd.CountBookmarks',
        'd.DateInserted',
        'd.DateUpdated',
        'd.InsertIPAddress',
        'd.UpdateIPAddress',
        'd.DateLastComment',
    );
/*
    protected $exportDiscussionFields = array(
        'DiscussionID',
        'CategoryID',
        'InsertUserID',
        'UpdateUserID',
        'FirstCommentID',
        'LastCommentID',
        'Name',
        'Body',
        'Format',
        'Tags',
        'CountComments',
        'CountBookmarks',
        'DateInserted',
        'DateUpdated',
        'InsertIPAddress',
        'UpdateIPAddress',
        'DateLastComment',
    );
*/
    protected $exportCommentFields = array(
        'CommentID',
        'DiscussionID',
        'CategoryID',
        'InsertUserID',
        'UpdateUserID',
        'FirstCommentID',
        'LastCommentID',
        'Name',
        'Body',
        'Format',
        'Tags',
        'CountComments',
        'CountBookmarks',
        'DateInserted',
        'DateUpdated',
        'InsertIPAddress',
        'UpdateIPAddress',
        'DateLastComment',
    );

    function __construct() {

        c('Plugins.CategoryExport.MaxDiscussions', self::DEFAULT_MAX);
    }

    public function pluginController_CategoryExport_Create($sender) {

        $sender->Title('Category Export');
        $sender->AddSideMenu('plugin/categoryexport');
        $sender->Form = new Gdn_Form();

        $this->Dispatch($sender, $sender->RequestArgs);
    }

    public function controller_index($sender) {

        $sender->Permission('Garden.Settings.Manage');
        $sender->SetData('PluginDescription',$this->GetPluginKey('Description'));

        /*
        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->SetField(array(
            'Plugin.CategoryExport.DEFAULT_MAX' => self::DEFAULT_MAX
        ));

        // Set the model on the form.
        $sender->Form->SetModel($configurationModel);
        */

        $catList = $this->getCategoryList();
        // echo "<pre>Cat List: ".print_r($catList,true)."</pre>\n";
        $sender->setData('CategoryList', $catList);

        $sender->Form->Action = "/plugin/categoryexport/download";
        $sender->Form->Method = "get";

        $sender->Render($this->GetView('export.php'));
    }
    public function controller_download($sender) {

        // @todo Capture GET data
        // @todo Make sure the number of rows does not exceed max value

        // INPUT Params:
        $table    = GDN::request()->get('table');
        $category = GDN::request()->get('CategoryID');
        $offset   = GDN::request()->get('offset', 0);
        $limit    = GDN::request()->get('limit', self::DEFAULT_MAX);

        switch ($table) {

            case 'discussions': case 'discussion':
                $data   = $this->getDiscussions($category, $limit, $offset);
                $fields = $this->exportDiscussionFields;
                break;
            case 'comments': case 'comment':
                $data   = $this->getComments($category);
                $fields = $this->exportCommentFields;
                break;
        }
//echo "<pre>Data: ".print_r($data,true)."</pre>\n";

        $csvHeader  = implode(",", $fields) . $this->lineSep;
        $csvContent = $this->parseCSV($data, $fields);
/*
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="export.csv"');
        header('Content-Length: '.(strlen($csvHeader) + strlen($csvContent)) );
*/
        echo $csvHeader;
        echo $csvContent;

        exit();
    }


    private function getCategoryList() {

        // @todo Replace with proper ORM query.
        //$categories = new CategoryModel();
        //$results    = $categories->getWhere(['AllowDiscussions'=>1])->resultArray();

        $sql  = "SELECT\n";
        $sql .= "  CategoryID\n";
        $sql .= ", Name\n";
        $sql .= "FROM GDN_Category\n";
        $sql .= "WHERE\n";
        $sql .= "  AllowDiscussions=1\n";
        $sql .= "  AND";
        $sql .= "  CountDiscussions > 0\n";
        $results = GDN::database()->connection()->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $output = [];
        foreach ($results AS $result) {
            $output[$result['CategoryID']] = $result['Name'];
        }
//echo "<pre>Categories: ".print_r($output,true)."</pre>\n";
        return $output;
    }

    private function getDiscussions($category, $limit=100, $offset=0) {
        //echo "Hello ".__METHOD__."()!\n";
        //$discussions = new DiscussionModel();
        //$results     = $discussions->getWhere(['CategoryID'=>$category], $offset, $limit);
        //$output      = $results->resultArray();

        $sql = Gdn::database()->SQL();
        $sql->select($this->exportDiscussionFields)
            ->from('Discussion AS d')
            ->leftJoin('User AS u', 'InsertUserID')
            ->where("CategoryID", $category)
            ->limit($limit,$offset)
        ;
        $results = $sql->get();
        $output  = $results->resultArray();
//echo "<pre>SQL Discussions: ".print_r($output,true)."</pre>\n";

        return $output;
    }

    private function getComments($category, $limit=10, $offset=0) {

        $comments    = new CommentModel();
        //$results     = $comments->getWhere(['CategoryID'=>$category], $offset, $limit);
        //$output      = $results->resultArray();
//echo "<pre>OUTPUT: ".print_r($output,true)."</pre>\n";
        //return $output;
    }


    private function parseCSV($input, $fields) {
        if (empty($fields) || !is_array($fields)) {
            return false;
        }

        $output = '';
        foreach ($input AS $item) {

            foreach ($fields AS $j => $field) {
                $output .= ($j!=0) ? $this->fieldSep : '';

                $fieldName = stristr($field,'.') ? substr($field, strpos($field,'.')+1) : $field;

                $value   = $item[$fieldName];
                $value   = str_replace($this->fieldSep, "\\".$this->fieldSep, $value);
                $value   = str_replace($this->escapeChar, "\\".$this->escapeChar, $value);
                $output .= "{$this->escapeChar}{$value}{$this->escapeChar}";
            }
            $output .= $this->lineSep;
        }

        return $output;
    }

    public function setLineSep($input) {
        if (empty($input) OR !is_string($input)) {
            return false;
        }
        $this->lineSep = $input;
    }
    public function setFieldSep($input) {
        if (empty($input) OR !is_string($input)) {
            return false;
        }
        $this->fieldSep = $input;
    }
    public function setEscapeChar($input) {
        if (empty($input) OR !is_string($input)) {
            return false;
        }
        $this->escapeChar = $input;
    }

} // Closes CategoryExport Plugin Class...
