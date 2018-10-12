/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { createAction, generateApiActionCreators, ActionsUnion } from "@library/state/utility";
import { IKbCategory } from "@knowledge/@types/api";
import ReduxActions from "@library/state/ReduxActions";

export default class CategoryActions extends ReduxActions {
    public static readonly GET_ALL_REQUEST = "@@kbCategories/GET_ALL_REQUEST";
    public static readonly GET_ALL_RESPONSE = "@@kbCategories/GET_ALL_RESPONSE";
    public static readonly GET_ALL_ERROR = "@@kbCategories/GET_ALL_ERROR";

    public static ACTION_TYPES: ActionsUnion<typeof CategoryActions.getCategoryACs>;

    // Raw actions for getting a knowledge category
    private static getCategoryACs = ReduxActions.generateApiActionCreators(
        CategoryActions.GET_ALL_REQUEST,
        CategoryActions.GET_ALL_RESPONSE,
        CategoryActions.GET_ALL_ERROR,
        // https://github.com/Microsoft/TypeScript/issues/10571#issuecomment-345402872
        {} as IKbCategory[],
        {},
    );

    // Usable action for getting a list of all categories.
    public getAllCategories() {
        return this.dispatchApi("get", "/knowledge-categories", CategoryActions.getCategoryACs, {});
    }
}
