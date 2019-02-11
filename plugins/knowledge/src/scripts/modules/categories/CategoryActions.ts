/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ActionsUnion } from "@library/state/utility";
import {
    IKbCategory,
    IPostKbCategoryRequestBody,
    IPostKbCategoryResponseBody,
    IPatchKbCategoryResponseBody,
    IPatchKbCategoryRequestBody,
} from "@knowledge/@types/api";
import ReduxActions, { bindThunkAction } from "@library/state/ReduxActions";
import {
    IDeleteKbCategoryRequest,
    IGetKbCategoryRequestBody,
    IGetKbCategoryResponseBody,
} from "@knowledge/@types/api/kbCategory";
import actionCreatorFactory from "typescript-fsa";
import { IApiError } from "@library/@types/api";

const createAction = actionCreatorFactory("@@category");

export default class CategoryActions extends ReduxActions {
    public static ACTION_TYPES:
        | ActionsUnion<typeof CategoryActions.deleteCategoryACs>
        | ActionsUnion<typeof CategoryActions.patchCategoryACs>
        | ActionsUnion<typeof CategoryActions.postCategoryACs>;

    public static readonly DELETE_CATEGORY_REQUEST = "@@kbCategories/DELETE_CATEGORY_REQUEST";
    public static readonly DELETE_CATEGORY_RESPONSE = "@@kbCategories/DELETE_CATEGORY_RESPONSE";
    public static readonly DELETE_CATEGORY_ERROR = "@@kbCategories/DELETE_CATEGORY_ERROR";

    // Raw actions for deleting a knowledge category.
    private static deleteCategoryACs = ReduxActions.generateApiActionCreators(
        CategoryActions.DELETE_CATEGORY_REQUEST,
        CategoryActions.DELETE_CATEGORY_RESPONSE,
        CategoryActions.DELETE_CATEGORY_ERROR,
        // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
        {},
        {} as IDeleteKbCategoryRequest,
    );

    // Usable action for deleting a category.
    public deleteCategory(id: number) {
        return this.dispatchApi("delete", `/knowledge-categories/${id}`, CategoryActions.deleteCategoryACs, {
            knowledgeCategoryID: id,
        });
    }

    public static getCategoryACs = createAction.async<IGetKbCategoryRequestBody, IGetKbCategoryResponseBody, IApiError>(
        "GET_CATEGORY",
    );

    // Usable action for deleting a category.
    public getCategory(request: IGetKbCategoryRequestBody) {
        const { id } = request;
        const apiThunk = bindThunkAction(CategoryActions.getCategoryACs, async () => {
            const response = await this.api.get(`/knowledge-categories/${id}?expand=all`);
            return response.data;
        })(request);
        return this.dispatch(apiThunk);
    }

    public static readonly POST_CATEGORY_REQUEST = "@@kbCategories/POST_CATEGORY_REQUEST";
    public static readonly POST_CATEGORY_RESPONSE = "@@kbCategories/POST_CATEGORY_RESPONSE";
    public static readonly POST_CATEGORY_ERROR = "@@kbCategories/POST_CATEGORY_ERROR";

    // Raw actions for getting all knowledge categories
    private static postCategoryACs = ReduxActions.generateApiActionCreators(
        CategoryActions.POST_CATEGORY_REQUEST,
        CategoryActions.POST_CATEGORY_RESPONSE,
        CategoryActions.POST_CATEGORY_ERROR,
        // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
        {} as IKbCategory,
        {} as IPostKbCategoryRequestBody,
    );

    /**
     * Create a new category.
     *
     * @param data The category data.
     */
    public postCategory = (data: IPostKbCategoryRequestBody) => {
        return this.dispatchApi<IPostKbCategoryResponseBody>(
            "post",
            "/knowledge-categories",
            CategoryActions.postCategoryACs,
            data,
        );
    };

    public static readonly PATCH_CATEGORY_REQUEST = "@@kbCategories/PATCH_CATEGORY_REQUEST";
    public static readonly PATCH_CATEGORY_RESPONSE = "@@kbCategories/PATCH_CATEGORY_RESPONSE";
    public static readonly PATCH_CATEGORY_ERROR = "@@kbCategories/PATCH_CATEGORY_ERROR";

    // Raw actions for getting all knowledge categories
    private static patchCategoryACs = ReduxActions.generateApiActionCreators(
        CategoryActions.PATCH_CATEGORY_REQUEST,
        CategoryActions.PATCH_CATEGORY_RESPONSE,
        CategoryActions.PATCH_CATEGORY_ERROR,
        // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
        {} as IPatchKbCategoryResponseBody,
        {} as IPatchKbCategoryRequestBody,
    );

    /**
     * Update a new category.
     *
     * @param data The category data.
     */
    public patchCategory = (data: IPatchKbCategoryRequestBody) => {
        return this.dispatchApi<IPatchKbCategoryResponseBody>(
            "patch",
            `/knowledge-categories/${data.knowledgeCategoryID}`,
            CategoryActions.patchCategoryACs,
            data,
        );
    };
}
