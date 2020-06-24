/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { combineReducers } from "redux";
import { productsReducer } from "@subcommunities/products/productReducer";
import { IMultiSiteState } from "@subcommunities/state/model";
import { subcommunityReducer } from "@subcommunities/subcommunities/subcommunityReducer";

const multiSiteReducer = combineReducers<IMultiSiteState>({
    products: productsReducer,
    subcommunities: subcommunityReducer,
});

export default multiSiteReducer;
