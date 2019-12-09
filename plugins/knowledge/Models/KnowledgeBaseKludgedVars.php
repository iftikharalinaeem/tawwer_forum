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
    const CHOOSER_TITLE_MAX_LENGTH = 50;

    const FG_MESSAGE = "Foreground colors are used mostly used for text and icons. This should contrast with the background color.";
    const BG_MESSAGE = "Background colors are used as the background of elements. This should have good constrast with the foreground color.";

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
     * @return mixed|null
     */
    public function readKludgedConfigValue(array $varInfo) {
        $configName = $this->makeConfigName($varInfo);
        $value = $this->config->get($configName, null);
        if ($value === '') {
            // The ConfigModule often saves empty strings from empty inputs.
            return null;
        }

        $inputType = $varInfo['Options']['type'] ?? null;
        if ($inputType === 'number') { // Number inputs should be kept as numbers!
            $value = (int) $value;
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
                "LabelCode" => self::t("Primary Color"),
                "Description" => self::t("The primary color is used for buttons, inputs, and various indicators."),
                "Control" => "color",
                'Options' => [
                    'AllowEmpty' => true,
                    'placeholder' => "#0291db",
                ],
            ],
            [
                "VariableName" => "global.mainColors.bg",
                "LabelCode" => self::t("Default Background Color"),
                "Description" => self::t(self::BG_MESSAGE),
                "Control" => "color",
                'Options' => [
                    'AllowEmpty' => true,
                    'placeholder' => "#ffffff"
                ],
            ],
            [
                "VariableName" => "global.mainColors.fg",
                "LabelCode" => self::t("Default Foreground Color"),
                "Description" => self::t(self::FG_MESSAGE),
                "Control" => "color",
                'Options' => [
                    'AllowEmpty' => true,
                    'placeholder' => '#555a62',
                ],
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
                "LabelCode" => self::t("Title Bar Background Color"),
                "Description" => self::t(self::BG_MESSAGE),
                "Control" => "color",
                'Options' => [
                    'AllowEmpty' => true,
                    'placeholder' => "#0291db",
                ]
            ],
            [
                "VariableName" => "titleBar.colors.fg",
                "LabelCode" => self::t("Title Bar Foreground Color"),
                "Description" => self::t(self::FG_MESSAGE),
                "Control" => "color",
                'Options' => [
                    'AllowEmpty' => true,
                    'placeholder' => "#ffffff"
                ]
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
                "VariableName" => "splash.title.text",
                "ConfigName" => "Knowledge.ChooserTitle",
                "LabelCode" => self::t("Knowledge Base Chooser Title"),
                "Description" => self::t("This title will appear on the Knowledge homepage. It should be 20 characters or less."),
                "Control" => "textbox",
                "Options" => [
                    "placeholder" => \Gdn::locale()->translate('How can we help you?'),
                    "maxlength" => self::CHOOSER_TITLE_MAX_LENGTH,
                ]
            ],
            [
                "VariableName" => "splash.outerBackground.image",
                "ConfigName" => "Knowledge.DefaultBannerImage",
                "Description" =>
                    self::t("The banner image to use on the knowledge base chooser. This can be overridden on a per-knoweldge base basis."
                    . " Recommended dimensions are about 1000px by 400px or a similar ratio."),
                "LabelCode" => self::t("Banner Image"),
                "Control" => "imageupload",
                "Options" => [
                    "RemoveConfirmText" => sprintf(self::t("Are you sure you want to delete your %s?"), self::t("banner image"))
                ],
            ],
            [
                "VariableName" => "splash.backgrounds.useOverlay",
                "LabelCode" => self::t("Use Banner Image Overlay"),
                "Description" => self::t("It can be hard to read text on top of certain banner images. "
                    . "Enable this setting to add an overlay over banner images which makes text easier to read."),
                "Control" => "toggle",
            ],
        ];
    }

    /**
     * Get variables related to content sizing.
     *
     * @return array[]
     */
    public function getSizingVariables(): array {
        return [
            [
                "VariableName" => "global.middleColumn.width",
                "ConfigName" => "Knowledge.MiddleColumn.Width",
                "LabelCode" => "Layout Center Column Width",
                "Description" => \Gdn::locale()->translate("The width of the center column of the primary layout in pixels."),
                "Control" => "textbox",
                "Options" => [
                    "placeholder" => "672",
                    "type" => "number",
                    "max" => 2000,
                    "min" => 500,
                ],
            ],
        ];
    }
}
