/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IServerError, IFieldError } from "@library/@types/api/core";

export interface IProduct {
    productID: number;
    siteSectionGroup: string;
    name: string;
    body?: string;
    dateInserted: string;
    dateUpdated: string;
    tempDeleted?: boolean;
}

export interface IProductDeleteError extends IFieldError {
    errorType: string;
    subcommunityCount: number;
    subcommunityIDs: number[];
}

/** @var string Site section prefix */
const SUBCOMMUNITY_SECTION_PREFIX = "subcommunities-section-";

/** @var string Site section group prefix */
const SUBCOMMUNITY_GROUP_PREFIX = "subcommunities-group-";

/** @var string Site section group prefix */
const SUBCOMMUNITY_NO_PRODUCT = "no-product";

export function makeSiteSectionID(data: { subcommunityID: number }): string {
    return SUBCOMMUNITY_SECTION_PREFIX + data.subcommunityID;
}

export function makeSiteSectionGroup(data: { productID: number | string | null }): string {
    const endPortion = data.productID === null ? SUBCOMMUNITY_NO_PRODUCT : data.productID;
    return SUBCOMMUNITY_GROUP_PREFIX + endPortion;
}
