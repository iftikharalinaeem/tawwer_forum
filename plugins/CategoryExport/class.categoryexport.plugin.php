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

    protected $fieldSep = ',';
    protected $lineSep  = "\r\n";

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
        //$sender->SetData('CategoryList', ['test'=>'Tester Array','bla'=>'BlaBla']);

        $sender->Render($this->GetView('config.php'));
    }
    public function controller_download($sender) {

        $discussions = $this->getDiscussions(2);
        //echo "<pre>Results: ".print_r($discussions,true)."</pre>\n";

        $csvHeader  = implode(",", $this->exportDiscussionFields) . $this->lineSep;
        $csvContent = $this->parseCSV($discussions);

        //header('Content-Type: text/csv');
        //header('Content-Disposition: attachment; filename="export.csv"');
        //header('Content-Length: '.(strlen($csvHeader) + strlen($csvContent)) );

        echo $csvHeader;
        echo $csvContent;
        //echo implode(",", $this->exportDiscussionFields) . $this->lineSep;
        //echo $this->parseCSV($discussions);

        exit();
    }


    private function getCategoryList() {

        $sql  = "SELECT\n";
        $sql .= "  CategoryID\n";
        $sql .= ", Name\n";
        $sql .= "FROM GDN_Category\n";
        $sql .= "WHERE\n";
        $sql .= "  AllowDiscussions=1\n";
        $sql .= "  AND";
        $sql .= "  (CountDiscussions > 0)\n";

        $results = GDN::database()->connection()->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $output = [];
        foreach ($results AS $result) {
            $output[$result['CategoryID']] = $result['Name'];
        }

        return $output;
    }

    private function getDiscussions($category=false, $limit=10, $offset=0) {

        $sql = Gdn::database()->SQL();
        $sql->select('*')
            ->from('Discussion')
            ->where("CategoryID", $category)
            ->limit($limit,$offset)
        ;
        $output = $sql->get()->resultArray();

        return $output;
    }


    private function parseCSV($input, $fields=false) {

        $fields = !empty($fields) ? $fields : $this->exportDiscussionFields;

        $output = '';
        foreach ($input AS $item) {

            foreach ($fields AS $j => $field) {
                $output .= ($j!=0) ? $this->fieldSep : '';

                $value = str_replace(',', '\,', $item[$field]);
                $output .= $value;
            }
            $output .= $this->lineSep;
        }

        return $output;
    }

} // Closes CategoryExport Plugin Class...
