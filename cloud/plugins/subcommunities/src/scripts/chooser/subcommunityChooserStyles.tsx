/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { dropDownVariables } from "@library/flyouts/dropDownStyles";
import { titleBarVariables } from "@library/headers/titleBarStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut, margins, paddings, unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { calc, percent, px } from "csx";
import { NestedCSSProperties } from "typestyle/lib/types";
import { SubcommunityChooserSection } from "@subcommunities/chooser/MultiLevelChooser";

export const subcommunityChooserVariables = useThemeCache(() => {
    const vars = variableFactory("subcommunityChooser");
    const titleBarVars = titleBarVariables();

    const options = vars("options", {
        forceIcon: titleBarVars.navAlignment.alignment === "center",
        defaultSection: "locale" as SubcommunityChooserSection,
        enabled: true,
    });

    const arrow = vars("arrow", {
        size: 12,
        margin: 6,
    });

    const toggle = vars("toggle", {
        lineHeight: unit(titleBarVars.button.size),
        minHeight: unit(titleBarVars.button.size),
        height: 0, // IE11 compat.
    });

    const dropDown = vars("dropDown", {
        maxHeightOffset: 100,
    });

    return { arrow, toggle, options, dropDown };
});

export const subcommunityChooserClasses = useThemeCache(() => {
    const style = styleFactory("subcommunityChooser");
    const vars = subcommunityChooserVariables();
    const dropdownVars = dropDownVariables();
    const globalVars = globalVariables();

    const toggle = style("toggle", {
        display: "inline-flex",
        alignItems: "center",
        lineHeight: unit(vars.toggle.lineHeight),
        fontWeight: globalVars.fonts.weights.normal,
    });

    const toggleFullWidth = style("toggleFullWidth", {
        width: percent(100),
    });

    const toggleArrow = style("toggleArrow", {
        ...margins({ horizontal: unit(4) }),
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
        maxHeight: calc(`100vh - ${unit(titleBarVariables().fullHeight + vars.dropDown.maxHeightOffset)}`),
    });

    const headingDivider = style("headingDivider", {
        position: "relative",
        display: "inline-block",
        ...margins({ horizontal: 12 }),
        height: px(12),
        width: px(2),
        background: colorOut(globalVars.border.color),
    });

    const check = style("check", {
        color: "inherit",
    });

    return {
        toggle,
        toggleFullWidth,
        toggleArrow,
        row,
        rowArrow,
        rowIndented,
        rowBack,
        body,
        headingDivider,
        check,
    };
});
