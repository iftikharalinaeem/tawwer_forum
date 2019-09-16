import { IServerError } from "@library/@types/api/core";

/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

export interface IProduct {
    productID: number;
    name: string;
    body?: string;
    dateInserted: string;
    dateUpdated: string;
    tempDeleted?: boolean;
}

export interface IProductDeleteError extends IServerError {
    errorType: string;
    subcommunityCount: number;
    subcommunityIDs: number[];
}
