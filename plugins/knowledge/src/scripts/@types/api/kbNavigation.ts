/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

interface ICommonNavigation {
    name: string;
    url: string;
    parentID: number;
    recordID: number;
    sort: number | null;
    recordType: "knowledgeCategory" | "article";
}

export interface IKbNavigationItem extends ICommonNavigation {
    children?: string[];
}

export interface IKbNavigationItemNested extends ICommonNavigation {
    children?: IKbNavigationItemNested[];
}

// API types
export interface IKbNavigationRequest {
    knowledgeBaseID?: number;
    knowledgeCategoryID?: number;
    maxDepth?: number;
}

export type IKbNavigationResponse = IKbNavigationItem[];
