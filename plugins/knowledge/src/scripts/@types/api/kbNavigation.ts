/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

export interface IKbNavigationItem {
    name: string;
    url: string;
    parentID: number;
    recordID: number;
    sort: number | null;
    children?: string[];
    recordType: "knowledgeCategory" | "article";
}

// API types
export interface IKbNavigationRequest {
    knowledgeBaseID?: number;
    knowledgeCategoryID?: number;
    maxDepth?: number;
}

export type IKbNavigationResponse = IKbNavigationItem[];
