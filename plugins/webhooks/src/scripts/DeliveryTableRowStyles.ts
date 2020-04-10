/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useThemeCache, styleFactory, variableFactory } from "@library/styles/styleUtils";
import { paddings, singleBorder, margins, colorOut } from "@library/styles/styleHelpers";
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

    cssOut(`${colDeliveryID} .media-title`, {
        ...margins({
            bottom: 0,
        }),
    });

    return { root, rowDelivery, colDeliveryID };
});
