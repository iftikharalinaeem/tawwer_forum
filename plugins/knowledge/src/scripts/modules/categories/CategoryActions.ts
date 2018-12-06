/**
 * @copyright 2009-2018 Vanilla Forums Inc.
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
import ReduxActions from "@library/state/ReduxActions";

export default class CategoryActions extends ReduxActions {
    public static ACTION_TYPES:
        | ActionsUnion<typeof CategoryActions.getAllCategoriesACs>
        | ActionsUnion<typeof CategoryActions.patchCategoryACs>
        | ActionsUnion<typeof CategoryActions.postCategoryACs>;

    public static readonly GET_ALL_REQUEST = "@@kbCategories/GET_ALL_REQUEST";
    public static readonly GET_ALL_RESPONSE = "@@kbCategories/GET_ALL_RESPONSE";
    public static readonly GET_ALL_ERROR = "@@kbCategories/GET_ALL_ERROR";

    // Raw actions for getting all knowledge categories
    private static getAllCategoriesACs = ReduxActions.generateApiActionCreators(
        CategoryActions.GET_ALL_REQUEST,
        CategoryActions.GET_ALL_RESPONSE,
        CategoryActions.GET_ALL_ERROR,
        // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
        {} as IKbCategory[],
        {},
    );

    // Usable action for getting a list of all categories.
    public getAllCategories() {
        return this.dispatchApi("get", "/knowledge-categories", CategoryActions.getAllCategoriesACs, {});
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
