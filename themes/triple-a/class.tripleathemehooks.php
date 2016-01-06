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
    public function base_Render_Before($sender) {
        $sender->addDefinition('swiperAutoplay', c('Garden.ThemeOptions.SwiperAutoplay', 5000));
    }
}
