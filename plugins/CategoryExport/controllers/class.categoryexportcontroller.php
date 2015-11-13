<?php

/**
 * @copyright 2010-2015 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 */

/**
 * Category Export interface
 * 
 * @package internal
 * @author Tim Gunter <tim@vanillaforums.com>
 * @since 1.0
 */
class CategoryExportController extends DashboardController {
    
    /**
     * Form reference
     * @var Gdn_Form
     */
    protected $form;
    
    /**
     * Hours between exports (globally)
     * @var integer
     */
    protected $cooldown;
    
    /**
     * Hours between exports (per category)
     * @var integer
     */
    protected $categoryCooldown;
    
    /**
     * Prepare controller for use
     * 
     * Handle common setup for all methods.
     */
    public function initialize() {
        parent::initialize();
        
        $this->cooldown = c('Plugins.CategoryExport.Cooldown', CategoryExportPlugin::DEFAULT_GLOBAL_COOLDOWN);
        $this->categoryCooldown = c('Plugins.CategoryExport.CategoryCooldown', CategoryExportPlugin::DEFAULT_CATEGORY_COOLDOWN);
        $this->form = new Gdn_Form;
    }

    /**
     * Category Export UI
     * 
     * Provide UI for downloading category export file.
     */
    public function index() {
        $this->title('Category Export');
        $this->permission('Garden.Settings.Manage');
        $this->addSideMenu('/categoryexport');
        
        $this->form->Method = 'post';
        
        $categoryID = $this->form->getValue('CategoryID', null);
        if ($this->form->authenticatedPostBack()) {
            
            $contents = $this->form->getValue('Contents');
            if (!is_array($contents)) {
                if (!empty($contents)) {
                    $contents = [$contents];
                } else {
                    $contents = [];
                }
            }
            if (!in_array('discussions', $contents)) {
                $contents[] = 'discussions';
            }
            $this->form->setFormValue('Contents', $contents);
            
            $this->form->validateRule('CategoryID', 'ValidateRequired');
            $this->form->validateRule('Contents', 'ValidateRequiredArray');
            $this->form->validateRule('Format', 'ValidateRequired');
            
            $format = $this->form->getValue('Format');
            if (!in_array($format, [
                CategoryExportPlugin::FORMAT_CSV, CategoryExportPlugin::FORMAT_JSON
            ])) {
                $this->form->addError(t('Requested format is not supported, please choose from the list.'));
            }
            
            if (!$this->form->errorCount()) {
                
                $include = $this->form->getValue('Contents', []);
                $options = [
                    'format' => $format
                ];
                
                try {
                    CategoryExportPlugin::export($categoryID, $include, $options);
                } catch (Exception $ex) {
                    $this->form->addError($ex);
                }
            }
        }
        
        $this->setData('cooldown', $this->cooldown);
        $this->setData('categorycooldown', $this->categoryCooldown);
        $this->setData('lastexport', CategoryExportPlugin::getLastExportDate());
        
        // Try to get export permission based on category if provided, global if not
        
        $canExport = CategoryExportPlugin::canExport($categoryID);
        $this->setData('canexport', $canExport);
        
        // If we cannot export yet, provide wait time
        if (!$canExport) {
            $cooldownRemaining = CategoryExportPlugin::getCooldownWait($categoryID);
            $delay = Gdn_Format::seconds($cooldownRemaining * 60);
            $this->setData('delay', $delay);
        }

        $this->render();
    }
    
    /**
     * 
     */
    protected static function toTime($minutes) {
        
    }
    
}