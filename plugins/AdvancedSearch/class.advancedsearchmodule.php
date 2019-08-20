<?php if (!defined('APPLICATION')) { exit(); }
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
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

    public $IncludeTags = true;

    public $Results = false; // whether or not to show results in the form.

    /**
     * @var array A mapping of type values to label codes.
     */
    public $Types = [];

    public $value = null;

    /**
     * @var \Vanilla\Sphinx\SphinxSearchModel
     */
    private $searchModel;

    /**
     * @var bool Whether or not to include the types filter on the output.
     */
    public $IncludeTypes = false;

    public function __construct($sender = '', $applicationFolder = false) {
        $this->_ApplicationFolder = 'plugins/AdvancedSearch';

        $this->DateWithinOptions = [
            '1 day' => plural(1, '%s day', '%s days'),
            '3 days' => plural(3, '%s day', '%s days'),
            '1 week' => plural(1, '%s week', '%s weeks'),
            '2 weeks' => plural(2, '%s week', '%s weeks'),
            '1 month' => plural(1, '%s month', '%s months'),
            '2 months' => plural(2, '%s month', '%s months'),
            '6 months' => plural(6, '%s month', '%s months'),
            '1 year' => plural(1, '%s year', '%s years')
        ];

        if (class_exists(\Vanilla\Sphinx\SphinxSearchModel::class)) {
            $this->searchModel = Gdn::getContainer()->get(\Vanilla\Sphinx\SphinxSearchModel::class);

            foreach ($this->searchModel->getSearchTypes() as $type) {
                $this->Types[$type->getApiValue()] = $type->getLabelCode();
            }
            $this->IncludeTypes = true;
        }
    }

    public static function addAssets() {
        Gdn::controller()->addJsFile('jquery.tokeninput.js');
        Gdn::controller()->addJsFile('jquery-ui.min.js');
        Gdn::controller()->addJsFile('advanced-search.js', 'plugins/AdvancedSearch');
        Gdn::controller()->addDefinition('TagHint', t('TagHint', 'Start to type...'));
        Gdn::controller()->addDefinition('TagSearching', t('Searching...'));
        Gdn::controller()->addDefinition('TagNoResults', t('No results'));
    }

    public function toString() {
        if ($this->IncludeTags === null) {
            $this->IncludeTags = c('Tagging.Discussions.Enabled') && Gdn::addonManager()->isEnabled('Sphinx', \Vanilla\Addon::TYPE_ADDON);
        }

        // We want the advanced search form to populate from the get and have lowercase fields.
        $form = $this->Form = new Gdn_Form();
        $form->Method = 'get';
        $get = array_change_key_case(Gdn::request()->get());

        if ($this->Results) {
            $form->formValues($get);
        } else {
            if ($this->value !== null && !isset($get['search'])) {
                $form->setFormValue('search', $this->value);
            }
        }

        // Add the tags as data.
        if (isset($get['tags'])) {
            $tags = explode(',', $get['tags']);
            $tags = array_filter($tags);
            $tags = Gdn::sql()->getWhere('Tag', ['Name' => $tags])->resultArray();
            if (count($tags) > 0 && isset($tags[0]['FullName'])) {
                $this->setData('Tags', array_column($tags, 'FullName', 'Name'));
            }
        }

        if ($this->IncludeTypes) {
            // See whether or not to check all of the  types.
            $types = $this->searchModel->fixOldSearchTypes($form->formValues());
            if (empty($types)) {
                // If nothing is checked then we count this as everything checked.
                $form->setFormValue('types', array_keys($this->Types));
            } else {
                $form->setFormValue('types', $types);
            }
        }

        return parent::toString();
    }
}
