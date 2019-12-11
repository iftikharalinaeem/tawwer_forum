/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { ActionsUnion, bindThunkAction } from "@library/redux/ReduxActions";
import {
    IDeleteKbCategoryRequest,
    IGetKbCategoryRequestBody,
    IGetKbCategoryResponseBody,
    IKbCategory,
    IPostKbCategoryRequestBody,
    IPostKbCategoryResponseBody,
    IPatchKbCategoryResponseBody,
    IPatchKbCategoryRequestBody,
} from "@knowledge/@types/api/kbCategory";
import actionCreatorFactory from "typescript-fsa";
import { IApiError } from "@library/@types/api/core";
import NavigationActions from "@knowledge/navigation/state/NavigationActions";
import { getCurrentLocale } from "@vanilla/i18n";

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
    public getCategory(request: IGetKbCategoryRequestBody): Promise<IGetKbCategoryResponseBody> {
        const { id } = request;
        const apiThunk = bindThunkAction(CategoryActions.getCategoryACs, async () => {
            const response = await this.api.get(`/knowledge-categories/${id}?expand=all&locale=${getCurrentLocale()}`);
            return response.data as IGetKbCategoryResponseBody;
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

    private navigationActions = new NavigationActions(this.dispatch, this.api);

    /**
     * Create a new category.
     *
     * @param data The category data.
     */
    public postCategory = async (data: IPostKbCategoryRequestBody) => {
        const categoryResponse = await this.dispatchApi<IPostKbCategoryResponseBody>(
            "post",
            "/knowledge-categories",
            CategoryActions.postCategoryACs,
            data,
        );

        if (categoryResponse && categoryResponse.data) {
            await this.navigationActions.getNavigationFlat(categoryResponse.data.knowledgeBaseID, true);
        }
        return categoryResponse;
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
    public patchCategory = async (data: IPatchKbCategoryRequestBody) => {
        const categoryResponse = await this.dispatchApi<IPatchKbCategoryResponseBody>(
            "patch",
            `/knowledge-categories/${data.knowledgeCategoryID}`,
            CategoryActions.patchCategoryACs,
            data,
        );

        if (categoryResponse && categoryResponse.data) {
            await this.navigationActions.getNavigationFlat(categoryResponse.data.knowledgeBaseID, true);
            await this.navigationActions.getTranslationSourceNavigationItems(categoryResponse.data.knowledgeBaseID);
        }
        return categoryResponse;
    };
}
