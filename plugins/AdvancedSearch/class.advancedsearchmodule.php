<?php if (!defined('APPLICATION')) exit();

/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */
class AdvancedSearchModule extends Gdn_Module {
    /**
     *
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
            '1 day' => Plural(1, '%s day', '%s days'),
            '3 days' => Plural(3, '%s day', '%s days'),
            '1 week' => Plural(1, '%s week', '%s weeks'),
            '2 weeks' => Plural(2, '%s week', '%s weeks'),
            '1 month' => Plural(1, '%s month', '%s months'),
            '2 months' => Plural(2, '%s month', '%s months'),
            '6 months' => Plural(6, '%s month', '%s months'),
            '1 year' => Plural(1, '%s year', '%s years')
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
        Gdn::Controller()->addJsFile('jquery.tokeninput.js');
        Gdn::Controller()->addJsFile('jquery-ui.js');
        Gdn::Controller()->addJsFile('advanced-search.js', 'plugins/AdvancedSearch');
        Gdn::Controller()->addDefinition('TagHint', t('TagHint', 'Start to type...'));
        Gdn::Controller()->addDefinition('TagSearching', t('Searching...'));
    }

    public function toString() {
        if ($this->IncludeTags === NULL) {
            $this->IncludeTags = Gdn::PluginManager()->IsEnabled('Tagging') && Gdn::PluginManager()->IsEnabled('Sphinx');
        }

        // We want the advanced search form to populate from the get and have lowercase fields.
        $Form = $this->Form = new Gdn_Form();
        $Form->Method = 'get';
        $Get = array_change_key_case(Gdn::Request()->Get());

        if ($this->Results) {
            $Form->formValues($Get);
        } else {
            if ($this->value !== null && !isset($Get['search']))
                $Form->setFormValue('search', $value);
        }

        // Add the tags as data.
        if (isset($Get['tags'])) {
            $tags = explode(',', $Get['tags']);
            $tags = array_filter($tags);
            $tags = Gdn::SQL()->GetWhere('Tag', array('Name' => $tags))->ResultArray();
            if (count($tags) > 0 && isset($tags[0]['FullName'])) {
                $this->SetData('Tags', ConsolidateArrayValuesByKey($tags, 'Name', 'FullName'));
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
