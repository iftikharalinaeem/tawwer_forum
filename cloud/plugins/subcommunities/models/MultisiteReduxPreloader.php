<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Subcommunities\Models;

use Garden\Web\Data;
use Vanilla\FeatureFlagHelper;
use Vanilla\Subcommunities\Controllers\Api\ProductsApiController;
use Vanilla\Subcommunities\Controllers\Api\SubcommunitiesApiController;
use Vanilla\Web\JsInterpop\ReduxAction;
use Vanilla\Web\JsInterpop\ReduxActionProviderInterface;

/**
 * Class for configuring preloading of Multisite Redux actions with our JsInterop stuff.
 * All configuration is done in the container, with data fetched lazily.
 */
class MultisiteReduxPreloader implements ReduxActionProviderInterface {

    const GET_ALL_SUBCOMMUNITIES_ACTION = "@@subcommunities/GET_ALL_DONE";
    const GET_ALL_PRODUCTS_ACTION = "@@products/GET_ALL_DONE";

    /** @var SubcommunitiesApiController */
    private $subcommunitiesApi;

    /** @var ProductsApiController */
    private $productsApi;

    /**
     * DI.
     *
     * @param SubcommunitiesApiController $subcommunitiesApi
     * @param ProductsApiController $productsApi
     */
    public function __construct(SubcommunitiesApiController $subcommunitiesApi, ProductsApiController $productsApi) {
        $this->subcommunitiesApi = $subcommunitiesApi;
        $this->productsApi = $productsApi;
    }

    /**
     * @inheritdoc
     */
    public function createActions(): array {
        $allCommunities = $this->subcommunitiesApi->index(['expand' => 'all']);

        $allProducts = [];
        if (FeatureFlagHelper::featureEnabled(ProductModel::FEATURE_FLAG)) {
            $allProducts = $this->productsApi->index();
        }

        return [
            new ReduxAction(self::GET_ALL_SUBCOMMUNITIES_ACTION, Data::box($allCommunities), []),
            new ReduxAction(self::GET_ALL_PRODUCTS_ACTION, Data::box($allProducts), []),
        ];
    }
}
