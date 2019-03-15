/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ICrumb } from "@library/navigation/Breadcrumbs";
import { IUserFragment } from "@library/@types/api/users";
import { IArticleFragment } from "@knowledge/@types/api/article";

export enum KbCategorySortMode {
    NAME = "name",
    DATE_INSERTED = "dateInserted",
    DATE_INSERTED_DESC = "dateInsertedDesc",
    MANUAL = "manual",
}

interface IKbCategoryRequiredData {
    name: string; // The human readable name of the category.
    parentID: number; // The parent category to promote a tree-structure.
}

interface IKbCategoryDefaultedData {
    sortChildren: KbCategorySortMode; // The default sort order of articles/child categories.
    sort: number; // The manual sort of the category.
}

interface IKbCategoryServerManagedData {
    knowledgeCategoryID: number; // The id.
    knowledgeBaseID: number;
    breadcrumbs?: ICrumb[];
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

export interface IDeleteKbCategoryRequest {
    knowledgeCategoryID: number;
}

export interface IPostKbCategoryRequestBody extends IKbCategoryRequiredData, Partial<IKbCategoryDefaultedData> {}

export interface IPostKbCategoryResponseBody extends IKbCategory {}

// Request/Response interfaces
export interface IPatchKbCategoryRequestBody extends Partial<IPostKbCategoryRequestBody> {
    knowledgeCategoryID: number;
}

export interface IPatchKbCategoryResponseBody extends IKbCategory {}

export interface IGetKbCategoryRequestBody {
    id: number;
    expand?: KbCategoryExpandFields[];
}

export interface IGetKbCategoryResponseBody extends IKbCategory {}

export interface IKbCategoryFragment {
    name: string; // The human readable name of the category.
    knowledgeCategoryID: number; // The id.
    parentID: number; // The parent category to promote a tree-structure.
    url: string; // Full URL to the resource
}
