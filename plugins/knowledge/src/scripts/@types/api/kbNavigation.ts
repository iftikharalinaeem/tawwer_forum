/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { INavigationItem } from "@library/@types/api";

export enum NavigationRecordType {
    KNOWLEDGE_CATEGORY = "knowledgeCategory",
    ARTICLE = "article",
}

// API types
export interface IGetKbNavigationRequest {
    knowledgeBaseID?: number;
    knowledgeCategoryID?: number;
    maxDepth?: number;
}

export type IGetKbNavigationResponse = INavigationItem[];

interface IPatchFlatItem {
    parentID: number;
    recordID: number;
    sort: number | null;
    recordType: NavigationRecordType;
}

export type IPatchKBNavigationRequest = IPatchFlatItem[];
export type IPatchKbNavigationResponse = INavigationItem[];
