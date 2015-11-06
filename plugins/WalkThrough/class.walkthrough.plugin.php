<?php if (!defined('APPLICATION')) exit();

/**
 * 
 * @copyright Copyright 2010 - 2015 Vanilla Forums Inc.
 * @license Proprietary
 */
$PluginInfo['WalkThrough'] = array(
    'Name' => 'Walk Through',
    'Description' => "Walks users through the features of the forum.",
    'Version' => '0.1',
    'RequiredApplications' => array('Vanilla' => '2.1a'),
    'Author' => 'Eric Vachaviolos',
    'AuthorEmail' => 'eric.v@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.org/profile/evach',
    'MobileFriendly' => false
);

class WalkThroughPlugin extends Gdn_Plugin {


    /// Event Handlers.


    /**
     * 
     * @param Gdn_Controller $Sender
     * @param type $args
     */
    public function base_render_before($Sender, $args) {
        
        if ($Sender->MasterView == 'admin') {
            // Do not show on the admin section
            return;
        }   
        
        if (!$this->shouldWeShowSteps()) {
            return;
        }
        
        $Sender->addCssFile('introjs.min.css', 'plugins/WalkThrough');
        $Sender->addJsFile('intro.min.js', 'plugins/WalkThrough');
        $Sender->addModule('WalkThroughModule');
    }

    
    /// METHODS

    
    private function shouldWeShowSteps() {
        if (Gdn::session()->getCookie('-intro_completed')) {
            return false;
        }
        return true;
    }
    
    /**
     * @todo Should be used by vfcom plugin to detect if it can push a tour
     * 
     * @param type $UserID
     * @param type $TourName
     */
    public function shouldUserSeeTour($UserID, $TourName) {
        
    }
    
    /**
     * @todo Should be used by vfcom plugin to push a new tour
     * 
     * @param type $TourName
     * @param type $Config
     */
    public function loadTour($TourName, $Config) {
        
    }
    

}
