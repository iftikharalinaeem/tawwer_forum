/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IKbState } from "@knowledge/state/model";
import ArticleModel from "@knowledge/modules/article/ArticleModel";
import ArticleMenuModel from "@knowledge/modules/article/ArticleMenuModel";
import { ARTICLE_PAGE_INITIAL_STATE } from "@knowledge/modules/article/ArticlePageModel";
import DraftsPageModel from "@knowledge/modules/drafts/DraftsPageModel";
import RevisionsPageModel from "@knowledge/modules/editor/RevisionsPageModel";
import EditorPageModel from "@knowledge/modules/editor/EditorPageModel";
import CategoryModel from "@knowledge/modules/categories/CategoryModel";
import { CATEGORY_PAGE_INITIAL_STATE } from "@knowledge/modules/categories/categoryPageReducer";
import LocationPickerModel from "@knowledge/modules/locationPicker/LocationPickerModel";
import NavigationModel from "@knowledge/navigation/state/NavigationModel";
import { ROUTE_INITIAL_STATE } from "@knowledge/routes/RouteReducer";
import KnowledgeBaseModel from "@knowledge/knowledge-bases/KnowledgeBaseModel";

const KB_STATE: IKbState = {
    articles: ArticleModel.INITIAL_STATE,
    articleMenu: ArticleMenuModel.INITIAL_STATE,
    articlePage: ARTICLE_PAGE_INITIAL_STATE,
    draftsPage: DraftsPageModel.INITIAL_STATE,
    revisionsPage: RevisionsPageModel.INITIAL_STATE,
    editorPage: EditorPageModel.INITIAL_STATE,
    categories: CategoryModel.INITIAL_STATE,
    categoriesPage: CATEGORY_PAGE_INITIAL_STATE,
    locationPicker: LocationPickerModel.INITIAL_STATE,
    navigation: NavigationModel.DEFAULT_STATE,
    route: ROUTE_INITIAL_STATE,
    knowledgeBases: KnowledgeBaseModel.INITIAL_STATE,
};

export const KB_TEST_INITIAL_STATE = {
    knowledge: KB_STATE,
};
