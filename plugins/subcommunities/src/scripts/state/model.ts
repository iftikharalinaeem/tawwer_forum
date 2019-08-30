import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { IProductsState } from "@subcommunities/products/productReducer";
import { Reducer } from "redux";

// export type SUBCOMMUNITY_ACTION =
// | typeof ProductActions.ACTION_TYPES;

export type SubcommunityReducer<S> = Reducer<S>;

export interface IMultiSiteState {
    products: IProductsState;
}

export interface IMultiSiteStoreState extends ICoreStoreState {
    multisite: IMultiSiteState;
}
