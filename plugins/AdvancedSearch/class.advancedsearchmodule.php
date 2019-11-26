<?php if (!defined('APPLICATION')) { exit(); }
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

use \Vanilla\Contracts\Search\SearchRecordTypeProviderInterface;
use \Vanilla\Contracts\Search\SearchRecordTypeInterface;

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

    public $Types = [];
    /**
     * @var SearchRecordTypeInterface[] $Types
     */
    public $recordTypes = [];

    public $value = null;

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

        /** @var SearchRecordTypeProviderInterface $recordTypesProvider */
        $recordTypesProvider = Gdn::getContainer()->get(SearchRecordTypeProviderInterface::class);
        $this->recordTypes = $recordTypesProvider->getAll();
        foreach ($this->recordTypes as $recordType) {
            $this->Types[$recordType->getCheckBoxId()] = $recordType->getCheckBoxLabel();
        }

    }

    public static function addAssets() {
        Gdn::controller()->addJsFile('jquery.tokeninput.js');
        Gdn::controller()->addJsFile('jquery-ui.min.js');
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
                $form->setFormValue('search', $value);
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

        // See whether or not to check all of the  types.
        $onechecked = false;
        /** @var SearchRecordTypeInterface $recordType */
        foreach ($this->recordTypes as $recordType) {
            if ($form->getFormValue($recordType->getCheckBoxId())) {
                $onechecked = true;
                break;
            }
        }
        if (!$onechecked) {
            foreach ($this->recordTypes as $recordType) {
                $form->setFormValue($recordType->getCheckBoxId(), true);
            }
        }

        return parent::toString();
    }
}
