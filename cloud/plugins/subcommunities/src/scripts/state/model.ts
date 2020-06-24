import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { IProductsState } from "@subcommunities/products/productReducer";
import { Reducer } from "redux";
import { ISubcommunitiesState } from "@subcommunities/subcommunities/subcommunityReducer";

export type SubcommunityReducer<S> = Reducer<S>;

export interface IMultiSiteState {
    products: IProductsState;
    subcommunities: ISubcommunitiesState;
}

export interface IMultiSiteStoreState extends ICoreStoreState {
    multisite: IMultiSiteState;
}
