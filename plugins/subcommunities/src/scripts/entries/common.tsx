/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import getStore from "@library/redux/getStore";
import { registerReducer } from "@library/redux/reducerRegistry";
import { getMeta, onReady } from "@library/utility/appUtils";
import { ProductActions } from "@subcommunities/products/ProductActions";
import multiSiteReducer from "@subcommunities/state/reducer";

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
