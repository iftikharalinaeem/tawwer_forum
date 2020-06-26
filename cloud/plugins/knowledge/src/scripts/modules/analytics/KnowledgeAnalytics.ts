/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IArticle } from "@knowledge/@types/api/article";
import { IKnowledgeBase } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { IKbCategory } from "@knowledge/@types/api/kbCategory";

/**
 * Given a full article, return only the fields relevant to an analytics event.
 */
export function articleEventFields(article: IArticle) {
    const { articleID, dateInserted, insertUserID, knowledgeBaseID, knowledgeCategoryID, name } = article;
    const result = {
        article: {
            articleID,
            dateInserted,
            insertUserID,
            name,
        },
        knowledgeBase: {
            knowledgeBaseID,
        },
        knowledgeCategory: {
            knowledgeCategoryID,
        },
    };
    return result;
}

/**
 * Given a full knowledge base, return only the fields relevant to an analytics event.
 */
export function knowledgeBaseEventFields(knowledgeBase: IKnowledgeBase) {
    const { knowledgeBaseID } = knowledgeBase;
    const result = {
        knowledgeBase: {
            knowledgeBaseID,
        },
    };
    return result;
}

/**
 * Given a full knowledge category, return only the fields relevant to an analytics event.
 */
export function knowledgeCategoryEventFields(knowledgeCategory: IKbCategory) {
    const { knowledgeBaseID, knowledgeCategoryID } = knowledgeCategory;
    const result = {
        knowledgeBase: {
            knowledgeBaseID,
        },
        knowledgeCategory: {
            knowledgeCategoryID,
        },
    };
    return result;
}
