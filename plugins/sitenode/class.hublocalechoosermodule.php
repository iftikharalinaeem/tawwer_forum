<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Renders a locale chooser dropdown.
 *
 * This is a different implementation from the hub's version of the same name. This version will grab its data from the
 * hub.
 */
class HubLocaleChooserModule extends Gdn_Module {
    public $label = 'Language';
    public $currentLabel = 'Language';

    public function __construct($Sender = '', $ApplicationFolder = false) {
        parent::__construct($Sender, 'plugins/sitenode');
    }

    private function getLocales() {
        $locales = []; //Gdn::cache()->get('hubLocales');
        if (empty($locales)) {
            try {
                /* @var SiteNodePlugin $plugin */
                $plugin = SiteNodePlugin::instance();
                $locales = $plugin->hubApi('/multisites/locales.json');
                $locales = val('Locales', $locales, []);
            } catch (Exception $ex) {
                $locales = [];
            }
            Gdn::cache()->store('hubLocales', $locales, [Gdn_Cache::FEATURE_EXPIRY => $locales ? 300 : 60]);
        }

        return $locales;
    }

    public function toString() {
        $this->setData('Locales', $this->getLocales());
        $this->setData('Current', $this->data('Locales.'.Gdn_Locale::canonicalize(Gdn::locale()->current())));

        if (empty($this->Data['Locales'])) {
            return '<!-- No locales -->';
        }
        return parent::toString();
    }
}
