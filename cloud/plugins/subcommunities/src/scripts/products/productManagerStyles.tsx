/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { flexHelper, singleBorder, unit, paddings } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { percent } from "csx";

export const productManagerVars = useThemeCache(() => {
    const makeVars = variableFactory("productManager");

    const sizes = makeVars("sizes", {
        rowHeight: 65,
    });

    return { sizes };
});

export const productManagerClasses = useThemeCache(() => {
    const vars = productManagerVars();
    const globalVars = globalVariables();
    const style = styleFactory("productManager");

    const row = style("row", {
        minHeight: unit(vars.sizes.rowHeight),
        width: percent(100),
        display: "flex",
        alignItems: "center",
        justifyContent: "space-between",
        $nest: {
            "& + &": {
                borderTop: singleBorder(),
            },
        },
    });

    const loader = style("loader", {
        $nest: {
            "&&": {
                justifyContent: "flex-start",
            },
        },
    });

    const emptyMessage = style("emptyMessage", {});

    const itemName = style("itemName", {
        display: "flex",
        flexDirection: "column",
        alignItems: "flex-start",
        justifyContent: "center",
        fontSize: globalVars.fonts.size.large,
        width: percent(100),
        flex: 1,
        ...paddings({ vertical: 12 }),
    });

    const item = style("item", {
        display: "flex",
        width: percent(100),
    });

    const itemActions = style("itemActions", {
        ...flexHelper().middle(),
        paddingLeft: 12,
    });

    const input = style("input", {
        flex: 1,
    });

    const error = style("error", {
        width: percent(100),
    });

    return { row, emptyMessage, loader, input, item, itemActions, itemName, error };
});
