<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Class TrustedContentPlugin
 *
 * Plugin to allow admins to white-list image sources that can be displayed in posts.
 */
class TrustedContentPlugin extends Gdn_Plugin {

    /**
     * Takes a block of user generated text and removes untrusted embedded content.
     *
     * @param $html
     * @return mixed
     */
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


    /**
     * Create a page in the dashboard to configure a white-list of trusted domains.
     *
     * @param SettingsController $sender
     * @param array $handler
     */
    public function settingsController_trustedContent_create($sender, $handler) {
        // Check permission
        $sender->permission('Garden.Settings.Manage');

        $form = $sender->Form;

        // Load up config options we'll be setting
        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField(['Garden.TrustedContentSources', 'Garden.HTML.FilterContentSources']);

        // Set the model on the form.
        $form->setModel($configurationModel);

        // If seeing the form for the first time...
        if ($form->authenticatedPostBack() === false) {
            // Format trusted domains as a string
            $trustedContentSources = val('Garden.TrustedContentSources', $configurationModel->Data);
            if (is_array($trustedContentSources)) {
                $trustedContentSources = implode("\n", $trustedContentSources);
            }

            // Apply the config settings to the form.
            $form->setData($configurationModel->Data);
        } else {
            // Format the embeddable domains as an array based on newlines & spaces
            $trustedContentSources = $form->getValue('Garden.TrustedContentSources');
            $trustedContentSources = explodeTrim("\n", $trustedContentSources);
            $trustedContentSources = array_unique($trustedContentSources);
            $trustedContentSources = implode("\n", $trustedContentSources);
            $form->setFormValue('Garden.TrustedContentSources', $trustedContentSources);

            // Save new settings
            $Saved = $form->save();
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
