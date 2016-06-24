<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Renders a locale chooser dropdown.
 */
class HubLocaleChooserModule extends Gdn_Module {
    public $label = 'Language';
    public $currentLabel = 'Language';
    public $cssClass = '';

    public function __construct($Sender = '', $ApplicationFolder = false) {
        parent::__construct($Sender, 'plugins/sitehub');
    }

    public function toString() {
        $this->setData('Locales', MultisiteModel::instance()->getNodeLocales());
        $this->setData('Current', $this->data('Locales.'.Gdn_Locale::canonicalize(Gdn::locale()->current())));

        switch (count($this->Data['Locales'])) {
            case 1:
                return '<!-- Only one locale -->';
            case 0:
                return '<!-- No locales -->';
            default:
                return parent::toString();
        }
    }
}
