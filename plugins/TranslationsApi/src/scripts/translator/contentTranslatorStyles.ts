/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useThemeCache, styleFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { percent, px } from "csx";
import { margins, paddings, unit } from "@library/styles/styleHelpers";
import { modalVariables } from "@library/modal/modalStyles";
import { NestedCSSProperties } from "typestyle/lib/types";
import { titleBarVariables } from "@vanilla/library/src/scripts/headers/titleBarStyles";

export const contentTranslatorClasses = useThemeCache(() => {
    const style = styleFactory("contentTranslator");
    const layoutVars = layoutVariables();
    const titleBarVars = titleBarVariables();
    const globalVars = globalVariables();

    const content = style("content", {
        paddingTop: unit(modalVariables().fullScreenTitleSpacing.gap),
        position: "relative",
        maxWidth: unit(800),
        width: percent(100),
        ...margins({ horizontal: "auto" }),
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

    const title = style("title", {
        display: "flex",
        ...paddings({
            left: unit(globalVars.gutter.half),
            right: unit(globalVars.gutter.half),
            top: globalVars.gutter.size - 6,
        }),
    });

    const translateIcon = style("translateIcon", {
        marginLeft: unit(12),
        marginRight: "auto",
    });

    return { content, header, translateIcon, title };
});
