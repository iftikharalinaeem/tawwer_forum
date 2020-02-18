/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useMemo } from "react";
import { IHomeWidgetContainerOptions } from "@vanilla/library/src/scripts/homeWidget/HomeWidgetContainer.styles";
import { IHomeWidgetItemOptions } from "@vanilla/library/src/scripts/homeWidget/HomeWidgetItem.styles";
import { HomeWidget } from "@vanilla/library/src/scripts/homeWidget/HomeWidget";
import { ISearchRequestBody } from "@knowledge/@types/api/search";
import { useArticleList } from "@knowledge/modules/article/ArticleModel";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import Loader from "@vanilla/library/src/scripts/loaders/Loader";
import { loaderClasses } from "@vanilla/library/src/scripts/loaders/loaderStyles";
import { IHomeWidgetItemProps } from "@vanilla/library/src/scripts/homeWidget/HomeWidgetItem";

interface IProps {
    containerOptions?: IHomeWidgetContainerOptions;
    itemOptions?: IHomeWidgetItemOptions;
    maxItemCount?: number;
    title?: string;
    params: ISearchRequestBody;
}

/**
 * Render a homepage widget populated with article data.
 */
export function ArticlesWidget(props: IProps) {
    const itemLoadable = useArticleList(props.params);
    const mappedItemData: IHomeWidgetItemProps[] | undefined = useMemo(() => {
        return itemLoadable.data?.map(searchResult => {
            const widgetItem: IHomeWidgetItemProps = {
                to: searchResult.url,
                name: searchResult.name,
                description: searchResult.body,
            };
            return widgetItem;
        });
    }, [itemLoadable.data]);

    if (itemLoadable.status === LoadStatus.LOADING) {
        return <Loader size={100} loaderStyleClass={loaderClasses().mediumLoader} />;
    }

    if (!mappedItemData || mappedItemData.length === 0) {
        return null;
    }

    return <HomeWidget {...props} itemData={mappedItemData} />;
}
