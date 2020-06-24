/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { variableFactory, useThemeCache } from "@vanilla/library/src/scripts/styles/styleUtils";
import { homeWidgetContainerVariables } from "@vanilla/library/src/scripts/homeWidget/HomeWidgetContainer.styles";
import { homeWidgetItemVariables } from "@vanilla/library/src/scripts/homeWidget/HomeWidgetItem.styles";

export enum ArticleWidgetPlacement {
    ABOVE = "above",
    BELOW = "below",
}

export const articleWidgetOptions = useThemeCache((overrides: any = {}) => {
    const makeVars = variableFactory("articleWidget");
    const defaultContainerOptions = homeWidgetContainerVariables().options;
    const defaultItemOptions = homeWidgetItemVariables().options;

    const optionsInit = makeVars("options", {
        helpCenterPlacement: ArticleWidgetPlacement.BELOW,
    });

    const options = makeVars(
        "options",
        {
            ...optionsInit,
            containerOptions: {
                ...defaultContainerOptions,
                maxColumnCount: optionsInit.helpCenterPlacement === ArticleWidgetPlacement.ABOVE ? 3 : 1,
            },
            itemOptions: {
                ...defaultItemOptions,
            },
            maxItemCount: optionsInit.helpCenterPlacement === ArticleWidgetPlacement.ABOVE ? 3 : 4,
        },
        overrides,
    );

    return options;
});
