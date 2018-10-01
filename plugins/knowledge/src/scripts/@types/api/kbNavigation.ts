/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IArticleFragment, IKbCategoryFragment } from "@knowledge/@types/api";
import { MultiTypeRecord } from "@library/@types/api";

// Base types
type IKbNavigationArticle = MultiTypeRecord<IArticleFragment, "articleID", "article">;

interface IKbNavigationCategory
    extends MultiTypeRecord<IKbCategoryFragment, "knowledgeCategoryID", "knowledgeCategory"> {
    children?: IKbNavigationItem[];
}

export type IKbNavigationItem = IKbNavigationCategory | IKbNavigationArticle;

// API types
interface IRequestWithBaseID {
    knowledgeBaseID: number;
    maxDepth?: number;
}

interface IRequestWithCategoryID {
    knowledgeCategoryID: number;
    maxDepth?: number;
}

export type IKbNavigationRequest = IRequestWithBaseID | IRequestWithCategoryID;

export type IKbNavigationResponse = IKbNavigationItem[];
