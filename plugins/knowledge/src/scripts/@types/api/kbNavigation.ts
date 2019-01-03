/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { INavigationItem } from "@library/@types/api";

export enum NavigationRecordType {
    KNOWLEDGE_CATEGORY = "knowledgeCategory",
    ARTICLE = "article",
}

export interface IKbNavigationItem extends INavigationItem {
    recordType: NavigationRecordType;
    children?: string[];
}

// API types
export interface IGetKbNavigationRequest {
    knowledgeBaseID?: number;
    knowledgeCategoryID?: number;
    maxDepth?: number;
}

export type IGetKbNavigationResponse = IKbNavigationItem[];

export interface IPatchFlatItem {
    parentID: number;
    recordID: number;
    sort: number | null;
    recordType: NavigationRecordType;
}

export type IPatchKBNavigationRequest = IPatchFlatItem[];
export type IPatchKbNavigationResponse = IKbNavigationItem[];
