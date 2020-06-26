/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LoadStatus } from "@library/@types/api/core";
import { getMeta } from "@library/utility/appUtils";
import { useProductActions } from "@subcommunities/products/ProductActions";
import { IProduct } from "@subcommunities/products/productTypes";
import { IMultiSiteStoreState } from "@subcommunities/state/model";
import { useSubcommunities } from "@subcommunities/subcommunities/subcommunitySelectors";
import { ISubcommunity } from "@subcommunities/subcommunities/subcommunityTypes";
import { useDebugValue, useEffect, useMemo } from "react";
import { useSelector } from "react-redux";
import { useCommunityFilterContext } from "@subcommunities/CommunityFilterContext";

export function useProductsState() {
    return useSelector((state: IMultiSiteStoreState) => {
        return state.multisite.products;
    });
}

export interface ICommunityLocaleGroup {
    product: IProduct | null;
    community: ISubcommunity;
}

export function useProducts() {
    const { getAll } = useProductActions();
    const { allProductLoadable, productsById, submittingProducts } = useProductsState();

    useEffect(() => {
        if (allProductLoadable.status === LoadStatus.PENDING) {
            getAll();
        }
    }, [allProductLoadable, getAll]);

    const result = { productsById, allProductLoadable, submittingProducts };
    useDebugValue(result);
    return result;
}

export function useProductsByLocale() {
    const { productsById } = useProducts();
    const { subcommunitiesByID } = useSubcommunities();
    const { hideNoProductCommunities } = useCommunityFilterContext();
    const productsByLocale = useMemo(() => {
        const communityData = subcommunitiesByID.data;
        if (!communityData) {
            return null;
        }

        const availableLocales = Array.from(new Set(Object.values(communityData).map(community => community.locale)));

        const communityGroupsByLocale: {
            [localeKey: string]: ICommunityLocaleGroup[];
        } = {};

        availableLocales.forEach(localeKey => {
            const communitiesForLocale = Object.values(communityData).filter(
                community => community.locale === localeKey,
            );
            communitiesForLocale.forEach(community => {
                const productLoadable = community.productID !== null ? productsById[community.productID] : undefined;
                if (hideNoProductCommunities && !productLoadable) {
                    return;
                }

                // Make the locale group if it doesn't already exist.
                if (!communityGroupsByLocale[localeKey]) {
                    communityGroupsByLocale[localeKey] = [];
                }

                communityGroupsByLocale[localeKey].push({
                    product: productLoadable ? productLoadable.product : null,
                    community,
                });
            });
        });

        return communityGroupsByLocale;
    }, [productsById, subcommunitiesByID]);
    useDebugValue(productsByLocale);
    return productsByLocale;
}

export function useProductsForLocale(localeKey: string | null) {
    const productsByLocale = useProductsByLocale();

    let result: ICommunityLocaleGroup[] | null = null;
    if (localeKey && productsByLocale) {
        result = productsByLocale[localeKey];
    }
    useDebugValue(result);
    return result;
}
