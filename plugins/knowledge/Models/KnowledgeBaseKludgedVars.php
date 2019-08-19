<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Knowledge\Models;

use Garden\StaticCacheTranslationTrait;
use Vanilla\Contracts\ConfigurationInterface;

/**
 * Class containing config-based kludged variables.
 *
 * This primarily exist as a stopgap until a more robust system for custom theming is display (such as through the API).
 * These values live as configuration, and are displayed through a ConfigurationModule.
 */
class KnowledgeBaseKludgedVars {

    use StaticCacheTranslationTrait;

    /** Maximum length allowed for the KB chooser page. */
    const CHOOSER_TITLE_MAX_LENGTH = 20;

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
     * Take a set of var data returned form this class, and prepare it for a Gdn_Form (or config module).
     *
     * @param array[] $varInfos
     * @return array[]
     */
    public function prepareAsFormValues(array $varInfos): array {
        $result = [];

        foreach ($varInfos as $varInfo) {
            $configName = $this->makeConfigName($varInfo);
            $result[$configName] = $varInfo;
        }
        return $result;
    }

    /**
     * Given some variable info, fetch it's value from the config and return it.
     *
     * @param array $varInfo
     * @return string|null
     */
    public function readKludgedConfigValue(array $varInfo): ?string {
        $configName = $this->makeConfigName($varInfo);
        $value = $this->config->get($configName, null);
        if ($value === '') {
            // The ConfigModule often saves empty strings from empty inputs.
            return null;
        }

        if ($value !== null && $varInfo['Control'] === "imageupload") {
            $value = \Gdn_Upload::url($value);
        }
        return $value;
    }

    /**
     * Given some variable info, fetch it's value from the config and return it.
     *
     * @param array $varInfo
     * @return string|null
     */
    public function makeConfigName(array $varInfo): string {
        return $varInfo['ConfigName'] ?? "Knowledge.ThemeKludge." . slugify($varInfo['VariableName']);
    }

    /**
     * Get global variable information colors.
     *
     * @return array[]
     */
    public function getGlobalColors(): array {
        return [
            [
                "VariableName" => "global.mainColors.primary",
                "LabelCode" => "Primary Color",
                "Description" => "The primary color is used for buttons, inputs, and various indicators.",
                "Control" => "color",
            ],
            [
                "VariableName" => "global.mainColors.bg",
                "LabelCode" => "Default Background Color",
                "Control" => "color",
            ],
            [
                "VariableName" => "global.mainColors.fg",
                "LabelCode" => "Default Text Color",
                "Control" => "color",
            ],
        ];
    }

    /**
     * Get titleBar variable information colors.
     *
     * @return array[]
     */
    public function getHeaderVars(): array {
        return [
            [
                "VariableName" => "titleBar.colors.bg",
                "LabelCode" => "Header Background Color",
                "Control" => "color",
            ],
            [
                "VariableName" => "titleBar.colors.fg",
                "LabelCode" => "Header Text Color",
                "Control" => "color",
            ],
            [
                "VariableName" => "titleBar.border.type",
                "LabelCode" => "Border Style",
                "Control" => "dropdown",
                "Items" => [
                    "none" => "None",
                    "shadow" => "Shadow",
                    "border" => "Border"
                ],
            ],
        ];
    }

    /**
     * Get variables for the banner image.
     *
     * @return array[]
     */
    public function getBannerVariables() {
        return [
            [
                "VariableName" => "splash.outerBackground.image",
                "ConfigName" => "Knowledge.DefaultBannerImage",
                "LabelCode" => "Banner Image",
                "Control" => "imageupload",
                "Options" => [
                    "RemoveConfirmText" => sprintf(self::t("Are you sure you want to delete your %s?"), self::t("banner image"))
                ],
            ],
            [
                "VariableName" => "splash.outerBackground.useOverlay",
                "LabelCode" => "Use Banner Image Overlay",
                "Description" => "It can be hard to read text on top of certain banner images. "
                    . "Enable this setting to add an overlay over banner images which makes text easier to read.",
                "Control" => "toggle",
            ],
            [
                "VariableName" => "splash.title.text",
                "ConfigName" => "Knowledge.ChooserTitle",
                "LabelCode" => "Knowledge Base Chooser Title",
                "Description" => "This title will appear on the Knowledge homepage. It should be 20 characters or less.",
                "Control" => "textbox",
                "Options" => [
                    "maxlength" => self::CHOOSER_TITLE_MAX_LENGTH,
                ]
            ]
        ];
    }
}
