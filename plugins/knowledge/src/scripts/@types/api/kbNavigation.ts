/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IArticleFragment, IKbCategoryMultiTypeFragment } from "@knowledge/@types/api";
import { MultiTypeRecord } from "@library/@types/api";

// Base types
type IKbNavigationArticle = MultiTypeRecord<IArticleFragment, "articleID", "article">;

export interface IKbNavigationCategory extends IKbCategoryMultiTypeFragment {
    children?: IKbNavigationItem[];
}

export type IKbNavigationItem = IKbNavigationCategory | IKbNavigationArticle;

// API types
export interface IKbNavigationRequest {
    knowledgeBaseID?: number;
    knowledgeCategoryID?: number;
    maxDepth?: number;
}

export type IKbNavigationResponse = IKbNavigationItem[];
