<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

$PluginInfo['TrustedContent'] = [
    'Name' => 'Trusted Embeded Content',
    'ClassName' => "TrustedContentPlugin",
    'Description' => 'Stop images from being shown on the forum unless it comes from a source that you trust. Removed images will be shown as a link.',
    'Version' => '1.0.0',
    'RequiredApplications' => ['Vanilla' => '2.2'],
    'SettingsUrl' => '/settings/trustedcontent',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'UsePopupSettings' => false,
    'MobileFriendly' => true,
    'Author' => 'Patrick Kelly',
    'AuthorEmail' => 'patrick.k@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.com'
];


/**
 * Class TrustedContentPlugin
 *
 * Plugin to allow admins to white-list image sources that can be displayed in posts.
 */
class TrustedContentPlugin extends Gdn_Plugin {


    public function format_filterHTML_handler($html) {
        $filtered = $html;
        // If the config does not have a SanitizeImages entry or it is set to false, pass the HTML to the renderer as is.
        if (!c('Garden.HTML.FilterContentSources', false)) {
            return $filtered;
        }

        preg_match_all('/\<img\s+src\s*=\s*[\"\'](.*?)[\"\'].*\>/', $html, $matches, PREG_SET_ORDER);
        // If no images are found in the HTML, pass the HTML to the renderer as is.
        if (!$matches) {
            return $filtered;
        }

        foreach ($matches as $image)  {
            if (!isset($image[0]) || !isset($image[1])) {
                continue;
            }

            $imageUrl = parse_url($image[1]);
            $imageHost = val('host', $imageUrl);

            if (in_array($imageHost,[Gdn::request()->host(), 'vanillaforums.com', 'vanillastaging.com', 'vanillacommunities.com', 'vanillacommunity.com'])) {
                continue;
            }

            // If the image is coming from this forum's CDN, do not strip this image.
            if ($imageHost === "us.v-cdn.net") {
                if (stringBeginsWith(val('path', $imageUrl), '/'.Infrastructure::siteID())) {
                    continue;
                }
            }

            // If the host of the image source is a trusted domain and not from the CDN, strip this image.
            if (!in_array(val('host', $imageUrl), explodeTrim("\n", c('Garden.TrustedContentSources')))) {
                $imageName = trim(substr(val('path', $imageUrl), strrpos(val('path', $imageUrl), '/')), '/');
                $filtered = str_replace($image[0], '<a href="'.$image[1].'">'.$imageName.'</a>', $filtered);
            }
        }

        return $filtered;
    }


    public function settingsController_trustedContent_create($sender, $handler) {
        // Check permission
        $sender->permission('Garden.Settings.Manage');

        // Load up config options we'll be setting
        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField(['Garden.TrustedContentSources', 'Garden.HTML.FilterContentSources']);

        // Set the model on the form.
        $sender->Form->setModel($configurationModel);

        // If seeing the form for the first time...
        if ($sender->Form->authenticatedPostBack() === false) {
            // Format trusted domains as a string
            $trustedContentSources = val('Garden.TrustedContentSources', $configurationModel->Data);
            if (is_array($trustedContentSources)) {
                $trustedContentSources = implode("\n", $trustedContentSources);
            }

            // Apply the config settings to the form.
            $sender->Form->setData($configurationModel->Data);
        } else {
            // Format the embeddable domains as an array based on newlines & spaces
            $trustedContentSources = $sender->Form->getValue('Garden.TrustedContentSources');
            $trustedContentSources = explodeTrim("\n", $trustedContentSources);
            $trustedContentSources = array_unique(array_filter($trustedContentSources));
            $trustedContentSources = implode("\n", $trustedContentSources);
            $sender->Form->setFormValue('Garden.TrustedContentSources', $trustedContentSources);

            // Save new settings
            $Saved = $sender->Form->save();
            if ($Saved !== false) {
                $sender->informMessage(t("Your changes have been saved."));
            }
        }

        $sender->setHighlightRoute('vanilla/settings/trustedcontent');
        $sender->title(t('Trusted Content Sources'));
        $sender->addJsFile('settings.js', 'plugins/trustedcontent');

        $sender->render('settings', '', 'plugins/trustedcontent');
    }
}
