/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache, variableFactory, styleFactory } from "@library/styles/styleUtils";
import { IThemeVariables } from "@library/theming/themeReducer";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut } from "@library/styles/styleHelpers";

export const userCardVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const makeVars = variableFactory("popupUserCard", forcedVars);
    const globalVars = globalVariables();
    const formElementsVars = formElementsVariables();

    const container = makeVars("card", {
        margin: globalVars.gutter.size,
    });

    const button = makeVars("button", {
        radius: formElementsVars.sizing.height / 2,
    });

    const name = makeVars("name", {
        size: globalVars.fonts.size.large,
        weight: globalVars.fonts.weights.bold,
    });

    const label = makeVars("label", {
        color: colorOut(globalVars.mainColors.primary),
        size: globalVars.fonts.size.small,
        padding: globalVars.gutter.quarter,
    });

    const vertical = makeVars("vertical", {
        color: colorOut(globalVars.border.color),
    });

    const count = makeVars("count", {
        size: globalVars.fonts.size.largeTitle,
    });

    return {
        container,
        button,
        name,
        label,
        vertical,
        count,
    };
});

export const userCardClasses = useThemeCache((props: { compact?: boolean } = {}) => {
    const style = styleFactory("popupUserCard");
    const vars = userCardVariables();

    const container = style("container", {
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
        justifyContent: "center",
        marginBottom: vars.container.margin,
    });

    const button = style("button", {
        borderRadius: vars.button.radius,
    });

    const name = style("name", {
        fontSize: vars.name.size,
        fontWeight: vars.name.weight,
    });

    const label = style("label", {
        color: vars.label.color,
        textTransform: "uppercase",
        fontSize: vars.label.size,
        border: `1px solid ${vars.label.color}`,
        padding: vars.label.padding,
    });

    const stat = style("stat", {
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
    });

    const vertical = style("vertical", {
        border: "none",
        background: vars.vertical.color,
    });

    const count = style("count", {
        fontSize: vars.count.size,
    });

    const header = style("header", {
        height: vars.container.margin * 2,
        display: "flex",
        flexDirection: "row-reverse",
    });

    const section = style("section", {
        paddingTop: vars.container.margin * 0.5,
    });

    return {
        container,
        button,
        name,
        label,
        stat,
        vertical,
        count,
        header,
        section,
    };
});
