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

    const buttonContainer = makeVars("buttonContainer", {
        padding: globalVars.gutter.half,
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

    const stat = makeVars("stat", {
        padding: 29,
    });

    const date = makeVars("date", {
        padding: globalVars.gutter.size,
    });

    return {
        container,
        button,
        buttonContainer,
        name,
        label,
        vertical,
        count,
        stat,
        date,
    };
});

export const userCardClasses = useThemeCache((props: { compact?: boolean } = {}) => {
    const style = styleFactory("popupUserCard");
    const vars = userCardVariables();

    const container = style("container", {
        display: "flex",
        flexDirection: "row",
        alignItems: "stretch",
        justifyContent: "center",
        marginBottom: vars.container.margin,
    });

    const button = style("button", {
        borderRadius: vars.button.radius,
    });

    const buttonContainer = style("buttonContainer", {
        paddingLeft: vars.buttonContainer.padding,
        paddingRight: vars.buttonContainer.padding,
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
        paddingLeft: vars.stat.padding,
        paddingRight: vars.stat.padding,
    });

    const vertical = style("vertical", {
        border: "none",
        background: vars.vertical.color,
    });

    const count = style("count", {
        fontSize: vars.count.size,
    });

    const header = style("header", {
        display: "flex",
        flexDirection: "row-reverse",
        alignItems: "center",
    });

    const section = style("section", {
        paddingTop: vars.container.margin * 0.5,
    });

    const email = style("email", {
        color: "inherit",
        textDecoration: "inherit",
    });

    const date = style("date", {
        paddingLeft: vars.date.padding,
        paddingRight: vars.date.padding,
    });

    return {
        container,
        button,
        buttonContainer,
        name,
        label,
        stat,
        vertical,
        count,
        header,
        section,
        email,
        date,
    };
});
