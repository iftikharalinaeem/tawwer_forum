/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { ProductActions } from "@subcommunities/products/ProductActions";
import { IProduct, IProductDeleteError } from "@subcommunities/products/productTypes";
import { produce } from "immer";
import { reducerWithInitialState } from "typescript-fsa-reducers";

export interface ILoadedProduct {
    product: IProduct;
    patchProduct: ILoadable<IProduct>;
    deleteProduct: ILoadable<{}, IProductDeleteError>;
}

type ProductLoadableGroup = {
    [id: number]: ILoadedProduct;
};

// Type for products that are currently being submitted.
export type TempProduct = Pick<IProduct, "name" | "body"> & { transactionID: string };
type TempProductLoadableGroup = { [tempID: string]: ILoadable<TempProduct> };

export interface IProductsState {
    enabled: boolean;
    enableStatus: LoadStatus;
    allProductLoadable: ILoadable<{}>;
    productsById: ProductLoadableGroup;
    submittingProducts: TempProductLoadableGroup;
}

const INITIAL_PRODUCTS_STATE: IProductsState = {
    enabled: false,
    enableStatus: LoadStatus.PENDING,
    allProductLoadable: {
        status: LoadStatus.PENDING,
    },
    productsById: {},
    submittingProducts: {},
};

export const productsReducer = produce(
    reducerWithInitialState<IProductsState>(INITIAL_PRODUCTS_STATE)
        .case(ProductActions.getAllACs.started, state => {
            state.allProductLoadable.status = LoadStatus.LOADING;
            return state;
        })
        .case(ProductActions.getAllACs.done, (state, payload) => {
            state.allProductLoadable.status = LoadStatus.SUCCESS;
            const products: ProductLoadableGroup = {};
            payload.result.forEach(product => {
                products[product.productID] = {
                    product,
                    patchProduct: { status: LoadStatus.PENDING },
                    deleteProduct: { status: LoadStatus.PENDING },
                };
            });
            state.productsById = products;
            return state;
        })
        .case(ProductActions.getAllACs.failed, (state, payload) => {
            state.allProductLoadable.status = LoadStatus.ERROR;
            state.allProductLoadable.error = payload.error;
            return state;
        })
        .case(ProductActions.postACs.started, (state, payload) => {
            state.submittingProducts[payload.transactionID] = {
                status: LoadStatus.PENDING,
                data: payload,
            };
            return state;
        })
        .case(ProductActions.postACs.done, (state, payload) => {
            delete state.submittingProducts[payload.params.transactionID];
            state.productsById[payload.result.productID] = {
                product: payload.result,
                patchProduct: { status: LoadStatus.PENDING },
                deleteProduct: { status: LoadStatus.PENDING },
            };
            return state;
        })
        .case(ProductActions.postACs.failed, (state, payload) => {
            state.submittingProducts[payload.params.transactionID] = {
                ...state.submittingProducts[payload.params.transactionID],
                status: LoadStatus.PENDING,
                error: payload.error,
            };
            return state;
        })
        .case(ProductActions.patchACs.started, (state, payload) => {
            const existingProduct = state.productsById[payload.productID];
            existingProduct.patchProduct = {
                status: LoadStatus.LOADING,
            };
            return state;
        })
        .case(ProductActions.patchACs.done, (state, payload) => {
            const existingProduct = state.productsById[payload.params.productID];
            existingProduct.product = payload.result;
            existingProduct.patchProduct = {
                status: LoadStatus.SUCCESS,
            };
            return state;
        })
        .case(ProductActions.patchACs.failed, (state, payload) => {
            const existingProduct = state.productsById[payload.params.productID];
            existingProduct.patchProduct = {
                status: LoadStatus.ERROR,
                error: payload.error,
            };
            return state;
        })
        .case(ProductActions.deleteACs.started, (state, payload) => {
            const existingProduct = state.productsById[payload.productID];
            existingProduct;
            existingProduct.deleteProduct.status = LoadStatus.LOADING;
            return state;
        })
        .case(ProductActions.deleteACs.done, (state, payload) => {
            delete state.productsById[payload.params.productID];
            return state;
        })
        .case(ProductActions.deleteACs.failed, (state, payload) => {
            const existingProduct = state.productsById[payload.params.productID];
            existingProduct.deleteProduct.status = LoadStatus.ERROR;
            existingProduct.deleteProduct.error = payload.error.response.data;
            return state;
        })
        .case(ProductActions.clearDeleteError, (state, payload) => {
            const existingProduct = state.productsById[payload.productID];
            existingProduct.deleteProduct = { status: LoadStatus.PENDING };
            return state;
        })
        .case(ProductActions.putFeatureFlagACs.started, (state, payload) => {
            state.enableStatus = LoadStatus.LOADING;
            return state;
        })
        .case(ProductActions.putFeatureFlagACs.done, (state, payload) => {
            state.enableStatus = LoadStatus.SUCCESS;
            state.enabled = payload.result.enabled;
            return state;
        }),
);
