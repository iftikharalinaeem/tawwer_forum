/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useThemeCache, styleFactory } from "@library/styles/styleUtils";
import { paddings, singleBorder, margins, colorOut } from "@library/styles/styleHelpers";
import { globalVariables } from "@vanilla/library/src/scripts/styles/globalStyleVars";
import { calc, quote, percent } from "csx";
import { NestedCSSProperties } from "typestyle/lib/types";

export const deliveryTabsCSSClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("tabs");

    const horizontalPadding = 18;
    const verticalPadding = globalVars.gutter.size / 2;
    const activeStyles = {
        "&::before": {
            content: quote(""),
            display: "block",
            position: "absolute",
            bottom: 0,
            ...margins({
                vertical: 0,
                horizontal: "auto",
            }),
            height: "2px",
            backgroundColor: colorOut(globalVars.mainColors.primary),
            width: calc(`${percent(100)} - ${horizontalPadding * 2}px`),
        },
    };

    const tabList = style("tabList", {
        display: "flex",
        borderBottom: singleBorder({ color: globalVars.separator.color, width: globalVars.separator.size }),
    });

    const tab = style("tab", {
        fontSize: globalVars.fonts.size.small,
        fontWeight: globalVars.fonts.weights.bold,
        position: "relative",
        textTransform: "uppercase",
        ...paddings({
            vertical: verticalPadding,
            horizontal: horizontalPadding,
        }),
        ...margins({
            bottom: "-1px",
        }),
        $nest: {
            "&:active": activeStyles as NestedCSSProperties,
        },
    });

    const panel = style("panel", {
        ...paddings({
            vertical: "24px",
            horizontal: horizontalPadding,
        }),
    });

    const isActive = style("isActive", activeStyles as NestedCSSProperties);

    return { tab, tabList, panel, isActive };
});
