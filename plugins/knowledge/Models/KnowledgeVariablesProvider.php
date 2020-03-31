<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Knowledge\Models;

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Theme\KludgedVariablesProviderInterface;
use Vanilla\Theme\VariablesProviderInterface;

/**
 * Provide theme variables specific to the Knowledge addon.
 */
class KnowledgeVariablesProvider implements KludgedVariablesProviderInterface {

    /** @var KnowledgeBaseKludgedVars */
    private $kludgedVars;

    /**
     * Initial configuration of the instance.
     *
     * @param KnowledgeBaseKludgedVars $kludgedVars
     */
    public function __construct(KnowledgeBaseKludgedVars $kludgedVars) {
        $this->kludgedVars = $kludgedVars;
    }

    /**
     * @inheritDoc
     */
    public function getVariables(): array {
        $kludgedVars = array_merge(
            $this->kludgedVars->getBannerVariables(),
            $this->kludgedVars->getHeaderVars(),
            $this->kludgedVars->getGlobalColors(),
            $this->kludgedVars->getSizingVariables()
        );
        $result = [];

        foreach ($kludgedVars as $varInfo) {
            $value = $this->kludgedVars->readKludgedConfigValue($varInfo);
            if ($value === null) {
                continue;
            }
            $varName = $varInfo['VariableName'];
            setvalr($varName, $result, $value);
        }

        return $result;
    }
}
