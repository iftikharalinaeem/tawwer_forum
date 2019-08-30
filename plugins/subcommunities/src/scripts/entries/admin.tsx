/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ProductSelectorFormGroup } from "@subcommunities/forms/ProductSelectorFormGroup";
import { addComponent } from "@library/utility/componentRegistry";
import { registerReducer } from "@library/redux/reducerRegistry";
import multiSiteReducer from "@subcommunities/state/reducer";
import { ProductIntegrationFormGroup } from "@subcommunities/forms/ProductIntegrationFormGroup";
import { onReady, getMeta } from "@library/utility/appUtils";
import getStore from "@library/redux/getStore";
import { ProductActions } from "@subcommunities/products/ProductActions";

addComponent("product-integration-form-group", ProductIntegrationFormGroup);
addComponent("product-selector-form-group", ProductSelectorFormGroup);
registerReducer("multisite", multiSiteReducer);

onReady(() => {
    const store = getStore();
    store.dispatch(
        ProductActions.putFeatureFlagACs.done({
            params: {},
            result: { status: getMeta("featureFlags.SubcommunityProducts.Enabled", false) ? "Enabled" : "Disabled" },
        }),
    );
});
