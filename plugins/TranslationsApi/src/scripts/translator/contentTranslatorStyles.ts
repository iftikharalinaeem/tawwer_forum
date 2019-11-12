/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useThemeCache, styleFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { percent, px } from "csx";
import { titleBarVariables } from "@library/headers/vanillaHeaderStyles";

export const contentTranslatorVariables = useThemeCache(() => {
    return {};
});

export const contentTranslatorClasses = useThemeCache(() => {
    const style = styleFactory("contentTranslator");
    const layoutVars = layoutVariables();
    const titleBarVars = titleBarVariables();

    const content = style("content", {
        paddingTop: globalVariables().gutter.size,
        maxWidth: layoutVars.middleColumn.width,
        width: percent(100),
    });

    const header = style("header", {
        display: "flex",
        justifyContent: "space-between",
        alignItems: "center",
        height: px(titleBarVars.sizing.height),
        ...layoutVars.mediaQueries().oneColumnDown({
            height: px(titleBarVars.sizing.mobile.height),
        }),
    });

    return { content, header };
});
