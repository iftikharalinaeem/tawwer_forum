/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { createAction, generateApiActionCreators, ActionsUnion } from "@library/state/utility";
import { IKbCategory, IPostKbCategoryRequestBody, IPostKbCategoryResponseBody } from "@knowledge/@types/api";
import ReduxActions from "@library/state/ReduxActions";

export default class CategoryActions extends ReduxActions {
    public static readonly GET_ALL_REQUEST = "@@kbCategories/GET_ALL_REQUEST";
    public static readonly GET_ALL_RESPONSE = "@@kbCategories/GET_ALL_RESPONSE";
    public static readonly GET_ALL_ERROR = "@@kbCategories/GET_ALL_ERROR";

    public static readonly POST_CATEGORY_REQUEST = "@@article/POST_CATEGORY_REQUEST";
    public static readonly POST_CATEGORY_RESPONSE = "@@article/POST_CATEGORY_RESPONSE";
    public static readonly POST_CATEGORY_ERROR = "@@article/POST_CATEGORY_ERROR";

    public static ACTION_TYPES: ActionsUnion<typeof CategoryActions.getAllCategoriesACs>;

    // Raw actions for getting all knowledge categories
    private static getAllCategoriesACs = ReduxActions.generateApiActionCreators(
        CategoryActions.GET_ALL_REQUEST,
        CategoryActions.GET_ALL_RESPONSE,
        CategoryActions.GET_ALL_ERROR,
        // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
        {} as IKbCategory[],
        {},
    );

    // Raw actions for getting all knowledge categories
    private static postCategoryACs = ReduxActions.generateApiActionCreators(
        CategoryActions.POST_CATEGORY_REQUEST,
        CategoryActions.POST_CATEGORY_RESPONSE,
        CategoryActions.POST_CATEGORY_ERROR,
        // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
        {} as IKbCategory,
        {},
    );

    // Usable action for getting a list of all categories.
    public getAllCategories() {
        return this.dispatchApi("get", "/knowledge-categories", CategoryActions.getAllCategoriesACs, {});
    }

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
}
