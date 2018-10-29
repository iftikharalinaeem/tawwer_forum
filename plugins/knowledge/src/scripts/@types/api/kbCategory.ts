/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IUserFragment } from "@library/@types/api";
import { IArticleFragment } from "@knowledge/@types/api";
import { MultiTypeRecord } from "@library/@types/api";

export enum KbCategoryDisplayType {
    ROOT = "root",
    HELP = "help",
    GUIDE = "guide",
    SEARCH = "search",
}

export enum KbCategorySortMode {
    NAME = "name",
    DATE_INSERTED = "dateInserted",
    DATE_INSERTED_DESC = "dateInsertedDesc",
    MANUAL = "manual",
}

interface IKbCategoryRequiredData {
    name: string; // The human readable name of the category.
    knowledgeCategoryID: number; // The id.
    parentID: number; // The parent category to promote a tree-structure.
    displayType: KbCategoryDisplayType; //How the category is layed out.
    isSection: boolean; // Determines if the category is cutoff point in navigation or not.
}

interface IKbCategoryDefaultedData {
    sortChildren: KbCategorySortMode; // The default sort order of articles/child categories.
    sort: number; // The manual sort of the category.
}

interface IKbCategoryServerManagedData {
    dateInserted: string;
    dateUpdated: string;
    insertUserID: number; // The user that inserted the article
    insertUser?: IUserFragment;
    updateUserID: number;
    updateUser?: IUserFragment;
    lastUpdatedArticleID: number;
    lastUpdatedArticle?: IArticleFragment;
    lastUpdatedUserID: number;
    lastUpdatedUser?: IUserFragment;
    url: string; // Full URL to the resource
}

type KbCategoryExpandFields = "user" | "lastArticle";

// The record
export interface IKbCategory extends IKbCategoryRequiredData, IKbCategoryDefaultedData, IKbCategoryServerManagedData {}

// Request/Response interfaces
export interface IPostKbCategoryRequestBody extends IKbCategoryRequiredData, Partial<IKbCategoryDefaultedData> {}

export interface IPostKbCategoryResponseBody extends IKbCategory {}

export interface IGetKbCategoryRequestBody {
    id: number;
    expand?: KbCategoryExpandFields[];
}

export interface IGetKbCategoryResponseBody extends IKbCategory {}

export interface IKbCategoryFragment {
    name: string; // The human readable name of the category.
    knowledgeCategoryID: number; // The id.
    parentID: number; // The parent category to promote a tree-structure.
    displayType: KbCategoryDisplayType; //How the category is layed out.
    isSection: boolean; // Determines if the category is cutoff point in navigation or not.
    url: string; // Full URL to the resource
}

export type IKbCategoryMultiTypeFragment = MultiTypeRecord<
    IKbCategoryFragment,
    "knowledgeCategoryID",
    "knowledgeCategory"
>;
