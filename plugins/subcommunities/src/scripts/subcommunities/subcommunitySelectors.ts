/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useSubcommunityActions } from "@subcommunities/subcommunities/SubcommunityActions";
import { IMultiSiteStoreState } from "@subcommunities/state/model";
import { useSelector } from "react-redux";
import { useEffect, useMemo, useDebugValue } from "react";
import { LoadStatus, ILoadable } from "@library/@types/api/core";
import { ISubcommunity, ILocale } from "@subcommunities/subcommunities/subcommunityTypes";
import { formatUrl, assetUrl, getMeta } from "@library/utility/appUtils";
import { useCommunityFilterContext } from "@subcommunities/CommunityFilterContext";

export function useSubcommunitiesState() {
    return useSelector((state: IMultiSiteStoreState) => {
        return state.multisite.subcommunities;
    });
}

export function useSubcommunities() {
    const { getAll } = useSubcommunityActions();
    const { subcommunitiesByID } = useSubcommunitiesState();

    useEffect(() => {
        if (subcommunitiesByID.status === LoadStatus.PENDING) {
            getAll();
        }
    }, [subcommunitiesByID, getAll]);

    const subcommunitiesByProductID = useMemo(() => {
        type ResultDataType = {
            [productID: number]: ISubcommunity[];
            noProduct: ISubcommunity[];
        };
        const result: ILoadable<ResultDataType> = {
            status: subcommunitiesByID.status,
            error: subcommunitiesByID.error,
        };

        if (subcommunitiesByID.data) {
            let data: ResultDataType = {
                noProduct: [],
            };

            Object.values(subcommunitiesByID.data).forEach(subcommunity => {
                const { productID } = subcommunity;

                if (productID !== null) {
                    // Check if we have the product already.
                    if (data[productID]) {
                        data[productID].push(subcommunity);
                    } else {
                        data[productID] = [subcommunity];
                    }
                } else {
                    data.noProduct.push(subcommunity);
                }
            });

            result.data = data;
        }
        return result;
    }, [subcommunitiesByID]);

    const result = { subcommunitiesByID, subcommunitiesByProductID };
    useDebugValue(result);
    return result;
}

export function useCurrentSubcommunity() {
    const webPath = formatUrl("");
    const realRoot = assetUrl("");
    const currentFolder = webPath.replace(realRoot, "").replace("/", "");
    const { subcommunitiesByID } = useSubcommunities();

    let result: ISubcommunity | null = null;
    if (subcommunitiesByID.data) {
        for (const community of Object.values(subcommunitiesByID.data)) {
            if (community.folder === currentFolder) {
                result = community;
            }
        }
    }

    useDebugValue(result);
    return result;
}

export function useAvailableLocales() {
    const { subcommunitiesByID } = useSubcommunities();
    const { hideNoProductCommunities } = useCommunityFilterContext();

    const result = useMemo(() => {
        const availableLocales: { [key: string]: ILocale } = {};
        if (!subcommunitiesByID.data) {
            return null;
        }

        for (const community of Object.values(subcommunitiesByID.data)) {
            if (availableLocales[community.locale]) {
                continue;
            }

            if (hideNoProductCommunities && community.productID == null) {
                continue;
            }

            availableLocales[community.locale] = {
                key: community.locale,
                translatedNames: community.localeNames,
            };
        }
        return availableLocales;
    }, [subcommunitiesByID, hideNoProductCommunities]);

    useDebugValue(result);
    return result;
}

export function useLocaleInfo() {
    const locales = useAvailableLocales();
    const result = useMemo(() => {
        if (!locales) {
            return null;
        }
        const result = {
            count: Object.values(locales).length,
            defaultLocale: getMeta("ui.localeKey", getMeta("ui.locale", null)),
        };
        return result;
    }, [locales]);
    useDebugValue(result);
    return result;
}
