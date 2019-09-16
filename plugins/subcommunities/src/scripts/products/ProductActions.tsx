/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IApiError, LoadStatus } from "@library/@types/api/core";
import ReduxActions, { bindThunkAction } from "@library/redux/ReduxActions";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import { IProduct, IProductDeleteError } from "@subcommunities/products/productTypes";
import { useMemo } from "react";
import { useDispatch } from "react-redux";
import { actionCreatorFactory } from "typescript-fsa";
import apiv2 from "@library/apiv2";
import { IMultiSiteStoreState } from "@subcommunities/state/model";
import { AxiosResponse } from "axios";

const actionCreator = actionCreatorFactory("@@products");

interface ITransactionParams {
    transactionID: string;
}

type GetAllRequest = {};
type GetAllResponse = IProduct[];
type GetRequest = { productID: number };
type GetResponse = IProduct;
type PostRequest = Pick<IProduct, "name" | "body">;
type PostResponse = IProduct;
type DeleteRequest = { productID: number };
type DeleteResponse = undefined;
type PatchRequest = Partial<PostRequest> & GetRequest;
type PatchResponse = IProduct;
type PutFeatureRequest = { enabled: boolean };
type PutFeatureResponse = { enabled: boolean };

export class ProductActions extends ReduxActions<IMultiSiteStoreState> {
    public static readonly getAllACs = actionCreator.async<GetAllRequest, GetAllResponse, IApiError>("GET_ALL");
    public static readonly getACs = actionCreator.async<GetRequest, GetResponse, IApiError>("GET");
    public static readonly postACs = actionCreator.async<
        PostRequest & ITransactionParams,
        PostResponse & ITransactionParams,
        IApiError
    >("POST");
    public static readonly patchACs = actionCreator.async<PatchRequest, PatchResponse, IApiError>("PATCH");
    public static readonly deleteACs = actionCreator.async<
        DeleteRequest,
        DeleteResponse,
        { response: AxiosResponse<IProductDeleteError> }
    >("DELETE");
    public static readonly putFeatureFlagACs = actionCreator.async<PutFeatureRequest, PutFeatureResponse, IApiError>(
        "PUT_FEATURE",
    );

    public static readonly clearDeleteError = actionCreator<{ productID: number }>("CLEAR_DELETE_ERRORS");

    public getAll = (force: boolean = false) => {
        if (!force && this.getState().multisite.products.allProductLoadable.status !== LoadStatus.PENDING) {
            // Only make the request if we haven't started it yet.
            return;
        }
        const apiThunk = bindThunkAction(ProductActions.getAllACs, async () => {
            const response = await this.api.get("/products");
            return response.data;
        })();
        return this.dispatch(apiThunk);
    };

    public postProduct = (request: PostRequest) => {
        const apiThunk = bindThunkAction(ProductActions.postACs, async () => {
            const response = await this.api.post("/products", request);
            return response.data;
        })({
            ...request,
            transactionID: uniqueIDFromPrefix("postProduct"),
        });
        return this.dispatch(apiThunk);
    };

    public patchProduct = (request: PatchRequest) => {
        const apiThunk = bindThunkAction(ProductActions.patchACs, async () => {
            const { productID, ...rest } = request;
            const response = await this.api.patch(`/products/${productID}`, rest);
            return response.data;
        })(request);
        return this.dispatch(apiThunk);
    };

    public deleteProduct = (request: DeleteRequest) => {
        const apiThunk = bindThunkAction(ProductActions.deleteACs, async () => {
            const { productID } = request;
            const response = await this.api.delete(`/products/${productID}`);
            return response.data;
        })(request);
        return this.dispatch(apiThunk);
    };

    public clearDeleteError = (productID: number) => {
        return this.dispatch(ProductActions.clearDeleteError({ productID }));
    };

    public toggleFeatureEnabled = (request: PutFeatureRequest) => {
        const apiThunk = bindThunkAction(ProductActions.putFeatureFlagACs, async () => {
            const response = await apiv2.put(`/products/product-feature-flag`, request);
            if (request.enabled) {
                await this.getAll(true); // Fetch products after enabling.
            }
            return response.data;
        })(request);
        return this.dispatch(apiThunk);
    };
}

export function useProductActions() {
    const dispatch = useDispatch();
    const actions = useMemo(() => {
        return new ProductActions(dispatch, apiv2);
    }, [dispatch]);
    return actions;
}
