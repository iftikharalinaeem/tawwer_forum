/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IApiDateInfo } from "@library/@types/api/core";
import { IProduct } from "@subcommunities/products/productTypes";

export interface ISubcommunity extends IApiDateInfo {
    subcommunityID: number;
    name: string;
    folder: string;
    categoryID: string | null;
    locale: string;
    sort: number;
    isDefault: boolean;
    productID: number | null;
    product?: IProduct;
}
