<?php
/**
 * Hooks for the Triple-A theme
 */
class TripleAThemeHooks extends Gdn_Plugin {

    /**
     * Hook in before the page is rendered, so we can modify any necessary data.
     * 
     * @param $sender Instance of current request controller
     */
    public function base_render_before($sender) {
        $sender->addDefinition('swiperAutoplay', c('Garden.ThemeOptions.SwiperAutoplay', 5000));
    }

    public function editorPlugin_getJSDefinitions_handler($sender) {
        Gdn::controller()->addDefinition('editorWysiwygCSS', asset('themes/triple-a/design/wysiwyg.css'));
    }
}
