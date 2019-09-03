/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IApiError } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import ReduxActions, { bindThunkAction } from "@library/redux/ReduxActions";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import { IProduct } from "@subcommunities/products/productTypes";
import { useMemo } from "react";
import { useDispatch } from "react-redux";
import { actionCreatorFactory } from "typescript-fsa";

const actionCreator = actionCreatorFactory("@@product");

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

export class ProductActions extends ReduxActions {
    public static readonly getAllACs = actionCreator.async<GetAllRequest, GetAllResponse, IApiError>("GET_ALL");
    public static readonly getACs = actionCreator.async<GetRequest, GetResponse, IApiError>("GET");
    public static readonly postACs = actionCreator.async<
        PostRequest & ITransactionParams,
        PostResponse & ITransactionParams,
        IApiError
    >("POST");
    public static readonly patchACs = actionCreator.async<PatchRequest, PatchResponse, IApiError>("PATCH");
    public static readonly deleteACs = actionCreator.async<DeleteRequest, DeleteResponse, IApiError>("DELETE");
    public static readonly putFeatureFlagACs = actionCreator.async<PutFeatureRequest, PutFeatureResponse, IApiError>(
        "PUT_FEATURE",
    );

    public getAll = () => {
        const apiThunk = bindThunkAction(ProductActions.getAllACs, async () => {
            const response = await apiv2.get("/products");
            return response.data;
        })();
        this.dispatch(apiThunk);
    };

    public postProduct = (request: PostRequest) => {
        const apiThunk = bindThunkAction(ProductActions.postACs, async () => {
            const response = await apiv2.post("/products", request);
            return response.data;
        })({
            ...request,
            transactionID: uniqueIDFromPrefix("postProduct"),
        });
        this.dispatch(apiThunk);
    };

    public patchProduct = (request: PatchRequest) => {
        const apiThunk = bindThunkAction(ProductActions.patchACs, async () => {
            const { productID, ...rest } = request;
            const response = await apiv2.patch(`/products/${productID}`, rest);
            return response.data;
        })(request);
        this.dispatch(apiThunk);
    };

    public deleteProduct = (request: DeleteRequest) => {
        const apiThunk = bindThunkAction(ProductActions.deleteACs, async () => {
            const { productID } = request;
            const response = await apiv2.delete(`/products/${productID}`);
            return response.data;
        })(request);
        this.dispatch(apiThunk);
    };

    public toggleFeatureEnabled = (request: PutFeatureRequest) => {
        const apiThunk = bindThunkAction(ProductActions.putFeatureFlagACs, async () => {
            const response = await apiv2.put(`/products/product-feature-flag`, request);
            return response.data;
        })(request);
        this.dispatch(apiThunk);
    };
}

export function useProductActions() {
    const dispatch = useDispatch();
    const actions = useMemo(() => {
        return new ProductActions(dispatch, apiv2);
    }, [dispatch]);
    return actions;
}
