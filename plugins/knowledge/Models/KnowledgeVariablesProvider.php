<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Knowledge\Models;

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Theme\VariablesProviderInterface;

/**
 * Provide theme variables specific to the Knowledge addon.
 */
class KnowledgeVariablesProvider implements VariablesProviderInterface {

    /** @var ConfigurationInterface */
    private $config;

    /**
     * Initial configuration of the instance.
     *
     * @param ConfigurationInterface $config
     */
    public function __construct(ConfigurationInterface $config) {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function getVariables(): array {
        $result = [
            'splash' => [],
            'global' => [
                'mainColors' => [],
            ],
            'titleBar' => [
                'colors' => [],
            ]
        ];

        $defaultBannerImage = $this->config->get("Knowledge.DefaultBannerImage", null);
        $useFilter = $this->config->get('Knowledge.ThemeKludge.UserBannerImageOverlay', null);
        if ($defaultBannerImage || $useFilter) {
            $bg = [];
            if (isset($defaultBannerImage)) {
                $bg["image"] = \Gdn_Upload::url($defaultBannerImage);
            }

            if (isset($useFilter)) {
                $bg["useFilter"] = $useFilter;
            }

            $result["splash"]["outerBackground"] = $bg;
        }

        if ($chooserTitle = $this->config->get("Knowledge.ChooserTitle")) {
            $result["splash"]["title"] = [
                "text" => $chooserTitle
            ];
        }

        $themeKludgeVars = $this->config->get('Knowledge.ThemeKludge');
        if ($themeKludgeVars) {
            if ($primaryColor = $themeKludgeVars['PrimaryColor'] ?? null) {
                $result['global']['mainColors']['primary'] = $primaryColor;
            }

            if ($fgColor = $themeKludgeVars['FgColor'] ?? null) {
                $result['global']['mainColors']['fg'] = $fgColor;
            }

            if ($bgColor = $themeKludgeVars['BgColor'] ?? null) {
                $result['global']['mainColors']['bg'] = $bgColor;
            }

            if ($titleBarFgColor = $themeKludgeVars['TitleBarFg'] ?? null) {
                $result['titleBar']['colors']['fg'] = $titleBarFgColor;
            }

            if ($titleBarBgColor = $themeKludgeVars['TitleBarBg'] ?? null) {
                $result['titleBar']['colors']['bg'] = $titleBarBgColor;
            }
        }

        return $result;
    }
}
