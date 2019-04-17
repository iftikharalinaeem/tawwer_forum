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
        $result = [];

        if ($defaultBannerImage = $this->config->get("Knowledge.DefaultBannerImage")) {
            $result["splash"] = [
                "outerBackground" => [
                    "image" => \Gdn_Upload::url($defaultBannerImage)
                ]
            ];
        }

        if ($chooserTitle = $this->config->get("Knowledge.ChooserTitle")) {
            if (!array_key_exists("splash", $result)) {
                $result["splash"] = [];
            }
            $result["splash"]["title"] = [
                "text" => $chooserTitle
            ];
        }

        return $result;
    }
}
