/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useThemeCache, styleFactory, variableFactory } from "@library/styles/styleUtils";
import { paddings, singleBorder, margins, colorOut, unit } from "@library/styles/styleHelpers";
import { globalVariables } from "@vanilla/library/src/scripts/styles/globalStyleVars";
import { calc, quote, percent } from "csx";
import { cssOut } from "@dashboard/compatibilityStyles";

export const deliveryTableRowCSSClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const horizontalPadding = 18;
    const verticalPadding = globalVars.gutter.size / 2;
    const style = styleFactory("DeliveryTableRow");

    const root = style({
        $nest: {
            "&&": {
                padding: 0,
            },
        },
    });

    const collapseLabel = style("collapseLabel", {
        color: colorOut(globalVars.links.colors.default),
        $nest: {
            "&&:not(.focus-visible)": {
                outline: 0,
            },
            "&&:hover, &&:focus, &&:active": {
                color: colorOut(globalVars.links.colors.active),
            },
        },
    });

    const rowDelivery = style("rowWrap", {
        display: "flex",
        alignItems: "stretch",
        width: percent(100),
    });

    const colDeliveryID = style("colDeliveryID", {
        display: "inline-flex",
        justifyContent: "flex-start",
        alignItems: "center",
        width: percent(35.7),
        ...paddings({
            vertical: verticalPadding,
            horizontal: horizontalPadding,
        }),
    });

    cssOut("div.column-xs", {
        display: "inline-flex",
        justifyContent: "flex-start",
        alignItems: "center",
        minWidth: "54px",
        width: percent(21.43),
        ...paddings({
            vertical: verticalPadding,
            horizontal: horizontalPadding,
        }),
    });

    cssOut(".table-data tbody tr.isOpen", {
        $nest: {
            "&:hover, &:focus": {
                backgroundColor: "transparent",
            },
        },
    });

    cssOut(".collapseIcon", {
        marginRight: unit(globalVars.gutter.half),
        transition: `transform ${globalVars.animation.defaultTiming} ${globalVars.animation.defaultTiming}`,
    });

    cssOut("tr", {
        $nest: {
            ".collapseDeliveryButton": {
                width: "auto",
            },
        },
    });

    cssOut("tr.isOpen", {
        $nest: {
            ".collapseIcon": {
                transform: "rotate(90deg)",
                color: globalVars.links.colors.active,
            },
        },
    });

    return { root, rowDelivery, colDeliveryID, collapseLabel };
});
