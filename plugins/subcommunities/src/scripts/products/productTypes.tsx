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
