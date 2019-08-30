/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LoadStatus } from "@library/@types/api/core";
import { useProductActions } from "@subcommunities/products/ProductActions";
import { IMultiSiteStoreState } from "@subcommunities/state/model";
import { useEffect } from "react";
import { useSelector } from "react-redux";

export function useProductsState() {
    return useSelector((state: IMultiSiteStoreState) => {
        return state.multisite.products;
    });
}

export function useProducts() {
    const { getAll } = useProductActions();
    const { allProductLoadable, productsById, submittingProducts } = useProductsState();

    useEffect(() => {
        if (allProductLoadable.status === LoadStatus.PENDING) {
            getAll();
        }
    }, [allProductLoadable, getAll]);

    return { productsById, allProductLoadable, submittingProducts };
}
