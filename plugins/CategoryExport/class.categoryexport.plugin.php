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
        $sender->SetData('CategoryList', $catList);
        //$sender->SetData('CategoryList', ['test'=>'Tester Array','bla'=>'BlaBla']);

        $sender->Render($this->GetView('config.php'));
    }
    public function controller_download($sender) {

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

    private function getDiscussions($category=false, $len=0, $start=0) {

    }

    private function parseCSV($input) {

    }

} // Closes CategoryExport Plugin Class...
