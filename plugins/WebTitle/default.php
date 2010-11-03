<?php if(!defined('APPLICATION')) die();

$PluginInfo['WebTitle'] = array(
    'Name' => 'Web Title',
    'Description' => 'Allows admins to change the title on the homepage. When you enable this plugin add a definition for <b>Title on Homepage</b> in your locale and it will be displayed ont he homepage.',
    'Version' => '1.1',
    'Author' => "Michal Toman",
    'RequiredApplications' => array('Vanilla' => '2.0'),
);

class WebTitle implements Gdn_IPlugin {

    /**
     * Base_Render_Before
     * @param Object $Sender
     */
    public function Base_Render_Before(&$Sender) {
        if($Sender->ControllerName == 'discussionscontroller') {
            $Sender->Head->Title(T('Title on Homepage', C('Garden.Title')));
        }
    }

    /**
     * Setup
     */
    public function Setup() {
    }
}

