/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useSubcommunityActions } from "@subcommunities/subcommunities/SubcommunityActions";
import { IMultiSiteStoreState } from "@subcommunities/state/model";
import { useSelector } from "react-redux";
import { useEffect, useMemo, useDebugValue } from "react";
import { LoadStatus, ILoadable } from "@library/@types/api/core";
import { ISubcommunity } from "@subcommunities/subcommunities/subcommunityTypes";
import { formatUrl, siteUrl, getMeta } from "@library/utility/appUtils";
import { useCommunityFilterContext } from "@subcommunities/CommunityFilterContext";
import { useLocaleInfo, ILocale } from "@vanilla/i18n";
import { Locale } from "moment";

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
            noProduct?: ISubcommunity[];
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

                if (productID != null) {
                    // Check if we have the product already.
                    if (data[productID]) {
                        data[productID].push(subcommunity);
                    } else {
                        data[productID] = [subcommunity];
                    }
                } else {
                    data.noProduct!.push(subcommunity);
                }
            });

            if (data.noProduct && data.noProduct.length === 0) {
                delete data.noProduct;
            }

            result.data = data;
        }
        return result;
    }, [subcommunitiesByID]);

    const result = { subcommunitiesByID, subcommunitiesByProductID };
    useDebugValue(result);
    return result;
}

export function useCurrentSubcommunity() {
    const webPath = getMeta("context.basePath", "");
    const realRoot = getMeta("context.host", "");
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

export function useAvailableSubcommunityLocales(): ILocale[] | null {
    const { subcommunitiesByID } = useSubcommunities();
    const { hideNoProductCommunities } = useCommunityFilterContext();
    const { locales } = useLocaleInfo();

    const result = useMemo(() => {
        const availableLocales: ILocale[] = [];
        if (!subcommunitiesByID.data) {
            return null;
        }

        for (const community of Object.values(subcommunitiesByID.data)) {
            const matchingFullLocale = locales.find(locale => community.locale === locale.regionalKey);

            if (!matchingFullLocale) {
                continue;
            }

            if (availableLocales.find(locale => locale.regionalKey === community.locale)) {
                continue;
            }

            if (hideNoProductCommunities && community.productID == null) {
                continue;
            }

            if (matchingFullLocale) {
                availableLocales.push(matchingFullLocale);
            }
        }
        return availableLocales;
    }, [subcommunitiesByID, hideNoProductCommunities, locales]);

    useDebugValue(result);
    return result;
}

type SubcommunityOrLocale = ISubcommunity | ILocale;

export function useSubcommunitiesOrLocales(): SubcommunityOrLocale[] {
    const { subcommunitiesByProductID } = useSubcommunities();
    const locales = useAvailableSubcommunityLocales() ?? [];
    const currentSubcommunity = useCurrentSubcommunity();

    const subcommunitiesForCurrentProduct = currentSubcommunity?.productID
        ? subcommunitiesByProductID?.data?.[currentSubcommunity.productID] ?? []
        : [];

    const results: SubcommunityOrLocale[] = [];

    locales.forEach(locale => {
        const matchingSubcommunity = subcommunitiesForCurrentProduct.find(subcommunity => {
            if (subcommunity.locale === locale.localeKey) {
                return subcommunity;
            }
        });

        if (matchingSubcommunity) {
            results.push(matchingSubcommunity);
        } else {
            results.push(locale);
        }
    });

    useDebugValue(results);

    return results;
}
