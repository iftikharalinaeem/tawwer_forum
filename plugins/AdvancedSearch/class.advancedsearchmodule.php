<?php if (!defined('APPLICATION')) { exit(); }
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Class AdvancedSearchModule
 */
class AdvancedSearchModule extends Gdn_Module {

    /**
     * @var Gdn_Form
     */
    public $Form;

    public $DateWithinOptions;

    public $IncludeTags = TRUE;

    public $Results = FALSE; // whether or not to show results in the form.

    public $Types = array();

    public $value = null;

    public function __construct($Sender = '', $ApplicationFolder = FALSE) {
        $this->_ApplicationFolder = 'plugins/AdvancedSearch';

        $this->DateWithinOptions = array(
            '1 day' => plural(1, '%s day', '%s days'),
            '3 days' => plural(3, '%s day', '%s days'),
            '1 week' => plural(1, '%s week', '%s weeks'),
            '2 weeks' => plural(2, '%s week', '%s weeks'),
            '1 month' => plural(1, '%s month', '%s months'),
            '2 months' => plural(2, '%s month', '%s months'),
            '6 months' => plural(6, '%s month', '%s months'),
            '1 year' => plural(1, '%s year', '%s years')
        );

        // Set the initial types.
        foreach (AdvancedSearchPlugin::$Types as $table => $types) {
            foreach ($types as $type => $label) {
                $value = $table.'_'.$type;
                $this->Types[$value] = $label;
            }
        }
    }

    public static function addAssets() {
        Gdn::controller()->addJsFile('jquery.tokeninput.js');
        Gdn::controller()->addJsFile('jquery-ui.js');
        Gdn::controller()->addJsFile('advanced-search.js', 'plugins/AdvancedSearch');
        Gdn::controller()->addDefinition('TagHint', t('TagHint', 'Start to type...'));
        Gdn::controller()->addDefinition('TagSearching', t('Searching...'));
    }

    public function toString() {
        if ($this->IncludeTags === NULL) {
            $this->IncludeTags = Gdn::pluginManager()->isEnabled('Tagging') && Gdn::pluginManager()->isEnabled('Sphinx');
        }

        // We want the advanced search form to populate from the get and have lowercase fields.
        $Form = $this->Form = new Gdn_Form();
        $Form->Method = 'get';
        $Get = array_change_key_case(Gdn::request()->get());

        if ($this->Results) {
            $Form->formValues($Get);
        } else {
            if ($this->value !== null && !isset($Get['search'])) {
                $Form->setFormValue('search', $value);
            }
        }

        // Add the tags as data.
        if (isset($Get['tags'])) {
            $tags = explode(',', $Get['tags']);
            $tags = array_filter($tags);
            $tags = Gdn::SQL()->getWhere('Tag', array('Name' => $tags))->resultArray();
            if (count($tags) > 0 && isset($tags[0]['FullName'])) {
                $this->setData('Tags', array_column($tags, 'FullName', 'Name'));
            }
        }

        // See whether or not to check all of the  types.
        $onechecked = false;
        foreach ($this->Types as $name => $label) {
            if ($Form->getFormValue($name)) {
                $onechecked = true;
                break;
            }
        }
        if (!$onechecked) {
            foreach ($this->Types as $name => $label) {
                $Form->setFormValue($name, true);
            }
        }

        return parent::toString();
    }
}
