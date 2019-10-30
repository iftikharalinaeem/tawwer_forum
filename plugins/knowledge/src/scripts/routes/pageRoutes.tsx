/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState } from "react";
import { Route } from "react-router-dom";
import ErrorPage from "@knowledge/pages/ErrorPage";
import qs from "qs";
import find from "lodash/find";
import { IKbNavigationItem } from "@knowledge/navigation/state/NavigationModel";
import { logWarning } from "@vanilla/utils";
import RouteHandler from "@library/routing/RouteHandler";
import ModalLoader from "@library/modal/ModalLoader";
import { IArticleFragment, IArticle, IArticleLocale } from "@knowledge/@types/api/article";
import { IRevisionFragment, IRevision } from "@knowledge/@types/api/articleRevision";
import { IKbCategory, IKbCategoryFragment } from "@knowledge/@types/api/kbCategory";
import { DefaultError } from "@knowledge/modules/common/PageErrorMessage";
import getStore from "@library/redux/getStore";
import { t, getSiteSection, siteUrl } from "@library/utility/appUtils";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { assetUrl } from "@library/utility/appUtils";
import { getCurrentLocale } from "@vanilla/i18n";
import { IKnowledgeBase, IKnowledgeBasesState } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { map } from "lodash";

interface IEditorURLData {
    articleID?: number;
    articleRevisionID?: number;
    draftID?: number;
    knowledgeCategoryID?: number | null;
    discussionID?: number | null;
    knowledgeBaseID?: number | null;
}

/**
 * Get the route for editing a particular article ID.
 *
 * @param articleID - The articleID.
 */
function makeEditorUrl(data?: IEditorURLData) {
    let baseUrl = "";
    const addRoot = "/kb/articles/add";
    let articleRedirection: boolean | undefined = undefined;

    if (!data) {
        return addRoot;
    }

    if (data.articleID === undefined) {
        if (data.knowledgeBaseID == null) {
            return addRoot;
        } else {
            const kbsByID = getStore<IKnowledgeAppStoreState>().getState().knowledge.knowledgeBases.knowledgeBasesByID;

            if (!kbsByID.data) {
                return addRoot;
            }

            const kb = kbsByID.data[data.knowledgeBaseID];
            if (!kb) {
                logWarning(
                    "Attempting to generate an editor URL for a knowledge base ID that doesn't exist",
                    data.knowledgeBaseID,
                );
                return addRoot;
            }
            const product = kb.siteSections.find(o => o.contentLocale === kb.sourceLocale) || null;
            const locale = getCurrentLocale();
            if (kb.sourceLocale !== locale) {
                if (!product) {
                    return addRoot;
                } else {
                    baseUrl = siteUrl(`${product.basePath}/kb/articles/add`);
                    articleRedirection = true;
                }
            } else {
                return addRoot;
            }
        }
        //baseUrl = addRoot;
    } else {
        baseUrl = `/kb/articles/${data.articleID}/editor`;
        articleRedirection = undefined;
    }

    let { knowledgeCategoryID } = data;
    const { articleRevisionID, draftID, knowledgeBaseID, discussionID } = data;
    if (knowledgeCategoryID !== undefined && knowledgeBaseID === undefined) {
        logWarning(
            "Attempted to initialize an editor with a categoryID but no knowledgeBaseID. They must both be provided",
        );
        knowledgeCategoryID = undefined;
    }
    const query = qs.stringify({
        articleRevisionID,
        draftID,
        knowledgeCategoryID,
        knowledgeBaseID,
        discussionID,
        articleRedirection,
    });

    if (query) {
        baseUrl += `?${query}`;
    }

    return baseUrl;
}

const editorPaths = ["/kb/articles/add", "/kb/articles/:id(\\d+)/editor"];

// Editor
const EDITOR_KEY = "EditorPageKey";
const loadEditor = () => import(/* webpackChunkName: "pages/kb/editor" */ "@knowledge/modules/editor/EditorPage");
export const EditorRoute = new RouteHandler(loadEditor, editorPaths, makeEditorUrl, ModalLoader, EDITOR_KEY);

/**
 * Get the route for editing a particular article ID.
 *
 * @param articleID - The articleID.
 */
export function makeRevisionsUrl(articleOrRevison: IArticleFragment | IArticle | IRevisionFragment | IRevision) {
    if ("articleRevisionID" in articleOrRevison) {
        return `/kb/articles/${articleOrRevison.articleID}/revisions/${articleOrRevison.articleRevisionID}`;
    } else {
        return `/kb/articles/${articleOrRevison.articleID}/revisions`;
    }
}
export const RevisionsRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/kb/revisions" */ "@knowledge/modules/editor/RevisionsPage"),
    "/kb/articles/:id(\\d+)/revisions/:revisionID(\\d+)?",
    makeRevisionsUrl,
    ModalLoader,
);

export const ArticleRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/kb/article" */ "@knowledge/modules/article/ArticlePage"),
    "/kb/articles/:id(\\d+)(-[^/]+)?",
    (article: IArticle | IArticleFragment) => article.url,
);

const CATEGORIES_KEY = "CategoriesPageKey";
export const CategoryRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/kb/categories" */ "@knowledge/modules/categories/CategoriesPage"),
    "/kb/categories/:id(\\d+)(-[^/]+)?",
    (category: IKbCategory | IKbCategoryFragment | IKbNavigationItem) => category.url,
    undefined,
    CATEGORIES_KEY,
);

export const CategoryPagedRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/kb/categories" */ "@knowledge/modules/categories/CategoriesPage"),
    "/kb/categories/:id(\\d+)(-[^/]+)?/p:page(\\d+)",
    (category: IKbCategory | IKbCategoryFragment | IKbNavigationItem) => category.url,
    undefined,
    CATEGORIES_KEY,
);

export const SearchRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/kb/search" */ "@knowledge/modules/search/SearchPage"),
    "/kb/search",
    (data?: undefined) => "/kb/search",
);

export const DraftsRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/kb/drafts" */ "@knowledge/modules/drafts/DraftsPage"),
    "/kb/drafts",
    (data?: undefined) => "/kb/drafts",
    ModalLoader,
);

export const HomeRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/kb/index" */ "@knowledge/pages/HomePage"),
    "/kb",
    (data?: undefined) => "/kb",
);

export const KnowledgeBasePage = new RouteHandler(
    () => import(/* webpackChunkName: "pages/kb/knowledge-base" */ "@knowledge/pages/KnowledgeBasePage"),
    "/kb/:urlCode([\\w\\d-]+)",
    (data: { urlCode: string }) => `/kb/${data.urlCode}`,
);

export const OrganizeCategoriesRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/kb/organize-categories" */ "@knowledge/pages/OrganizeCategoriesPage"),
    "/kb/:id/organize-categories",
    (data: { kbID: number }) => `/kb/${data.kbID}/organize-categories`,
    ModalLoader,
);

const NotFound = () => {
    return <ErrorPage defaultError={DefaultError.NOT_FOUND} />;
};

/**
 * Get the data for routes that can render in a modal.
 *
 * If a data can also be shown in the Modal container place it in {@link getModalRouteData}
 *
 * We can't return actual react components here because the React Router <Switch>
 * only looks at its direct children. Trying to join separate components of routes using
 * <React.Fragment> does not currently work.
 */
export function getPageRoutes() {
    return [
        EditorRoute.route,
        RevisionsRoute.route,
        ArticleRoute.route,
        CategoryRoute.route,
        CategoryPagedRoute.route,
        SearchRoute.route,
        DraftsRoute.route,
        OrganizeCategoriesRoute.route,
        KnowledgeBasePage.route,
        HomeRoute.route,
        <Route component={NotFound} key={"not found"} />,
    ];
}

// The point where `mountReact` or ReactDOM.render() is what will re-render the tree.
// This is only here to prevent changes to these modules from causing a full reload.
if (module.hot) {
    module.hot.accept();
}
