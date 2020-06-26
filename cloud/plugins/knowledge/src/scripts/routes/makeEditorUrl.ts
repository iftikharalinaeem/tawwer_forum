/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import getStore from "@library/redux/getStore";
import { siteUrl } from "@library/utility/appUtils";
import { getCurrentLocale } from "@vanilla/i18n";
import { logWarning } from "@vanilla/utils";
import qs from "qs";
import { Store } from "redux";

export interface IEditorURLData {
    articleID?: number;
    articleRevisionID?: number;
    draftID?: number;
    knowledgeCategoryID?: number | null;
    discussionID?: number | null;
    knowledgeBaseID?: number | null;
}

function getAddRoot(kbID: number | null | undefined, store?: Store<IKnowledgeAppStoreState>): string | null {
    if (kbID == null) {
        return null;
    }

    store = store ?? getStore<IKnowledgeAppStoreState>();
    const kbsByID = store.getState().knowledge.knowledgeBases.knowledgeBasesByID;

    if (!kbsByID.data) {
        return null;
    }

    const kb = kbsByID.data[kbID];
    if (!kb) {
        return null;
    }
    const product = kb.siteSections.find(o => o.contentLocale === kb.sourceLocale) || null;

    const locale = getCurrentLocale();
    if (kb.sourceLocale === locale) {
        return null;
    }

    if (!product) {
        return null;
    }

    return siteUrl(`${product.basePath}/kb/articles/add`);
}

/**
 * Get the route for editing a particular article ID.
 *
 * @param articleID - The articleID.
 */
export function makeEditorUrl(data?: IEditorURLData, store?: Store<IKnowledgeAppStoreState>) {
    const defaultAddRoot = "/kb/articles/add";
    if (!data) {
        return defaultAddRoot;
    }

    store = store ?? getStore<IKnowledgeAppStoreState>();
    const customAddRoot = getAddRoot(data.knowledgeBaseID);
    const addRoot = customAddRoot ?? defaultAddRoot;
    const articleRedirection = customAddRoot ? true : undefined;

    let baseUrl = data.articleID ? `/kb/articles/${data.articleID}/editor` : addRoot;
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
