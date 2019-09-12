/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { dropDownVariables } from "@library/flyouts/dropDownStyles";
import { titleBarVariables } from "@library/headers/titleBarStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut, margins, paddings, unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { percent, px } from "csx";
import { NestedCSSProperties } from "typestyle/lib/types";

export const chooserVariables = useThemeCache(() => {
    const vars = variableFactory("chooser");
    const titleBarVars = titleBarVariables();

    const arrow = vars("arrow", {
        size: 12,
        margin: 6,
    });

    const toggle = vars("toggle", {
        lineHeight: titleBarVars.button.size,
        color: titleBarVars.colors.fg,
    });

    return { arrow, toggle };
});

export const chooserClasses = useThemeCache(() => {
    const style = styleFactory("chooser");
    const vars = chooserVariables();
    const dropdownVars = dropDownVariables();
    const globalVars = globalVariables();

    const toggle = style("toggle", {
        display: "inline-flex",
        alignItems: "center",
        lineHeight: unit(vars.toggle.lineHeight),
        color: colorOut(vars.toggle.color),
        fontWeight: globalVars.fonts.weights.normal,
        ...margins({ horizontal: unit(6) }),
    });

    const toggleArrow = style("toggleArrow", {
        marginLeft: unit(6),
    });

    const rowMixin: NestedCSSProperties = {
        display: "flex",
        alignItems: "center",
        width: percent(100),
        justifyContent: "space-between",
    };

    const row = style("row", {
        ...rowMixin,
    });

    const rowArrow = style("rowArrow", {
        $nest: {
            "&&": {
                height: unit(vars.arrow.size),
                width: unit(vars.arrow.size),
            },
        },
    });

    const rowIndented = style("rowIndented", {
        ...rowMixin,
        paddingLeft: unit(vars.arrow.size),
    });

    const rowBack = style("rowBack", {
        ...rowMixin,
        justifyContent: "flex-start",
        marginLeft: unit(-vars.arrow.margin),
        $nest: {
            [`.${rowArrow}`]: {
                marginRight: unit(vars.arrow.margin),
            },
        },
    });

    const body = style("body", {
        ...paddings({ vertical: unit(dropdownVars.spacer.margin.vertical) }),
    });

    const headingDivider = style("headingDivider", {
        position: "relative",
        display: "inline-block",
        ...margins({ horizontal: 12 }),
        height: px(12),
        width: px(2),
        background: colorOut(globalVars.border.color),
    });

    return { toggle, toggleArrow, row, rowArrow, rowIndented, rowBack, body, headingDivider };
});
