/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache, variableFactory, styleFactory } from "@library/styles/styleUtils";
import { IThemeVariables } from "@library/theming/themeReducer";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut } from "@library/styles/styleHelpers";
import { clickableItemStates } from "@dashboard/compatibilityStyles/clickableItemHelpers";
import { layoutVariables } from "@library/layout/panelLayoutStyles";

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
        border: globalVars.border.radius,
        size: globalVars.fonts.size.small,
        padding: globalVars.gutter.quarter,
    });

    const vertical = makeVars("vertical", {
        color: colorOut(globalVars.border.color),
    });

    const containerWithBorder = makeVars("containerWithBorder", {
        color: colorOut(globalVars.border.color),
        padding: globalVars.gutter.size,
    });

    const count = makeVars("count", {
        size: globalVars.fonts.size.largeTitle,
    });

    const header = makeVars("header", {
        height: formElementsVars.sizing.height,
    });

    const date = makeVars("date", {
        padding: globalVars.gutter.size,
    });

    const email = makeVars("email", {
        color: colorOut(globalVars.mainColors.fg),
    });

    return {
        container,
        button,
        buttonContainer,
        name,
        label,
        vertical,
        containerWithBorder,
        count,
        header,
        date,
        email,
    };
});

export const userCardClasses = useThemeCache((props: { compact?: boolean } = {}) => {
    const style = styleFactory("popupUserCard");
    const vars = userCardVariables();
    const linkColors = clickableItemStates();
    const mediaQueries = layoutVariables().mediaQueries();

    const container = style("container", {
        display: "flex",
        flexDirection: "row",
        alignItems: "stretch",
        justifyContent: "center",
        marginBottom: vars.container.margin,
        flexWrap: "wrap",
    });

    const containerWithBorder = style("containerWithBorder", {
        borderTop: `1px solid ${vars.containerWithBorder.color}`,
        paddingTop: vars.containerWithBorder.padding,
    });

    const button = style("button", {
        display: "block",
        $nest: {
            "&:hover": {
                backgroundColor: colorOut(globalVariables().mainColors.primary),
                color: colorOut(globalVariables().mainColors.bg),
                border: `1px solid ${colorOut(globalVariables().mainColors.bg)}`,
            },
        },
        borderRadius: vars.button.radius,
    });

    const buttonContainer = style("buttonContainer", {
        padding: vars.buttonContainer.padding,
    });

    const name = style(
        "name",
        {
            fontSize: vars.name.size,
            fontWeight: vars.name.weight,
        },

        mediaQueries.oneColumnDown({
            fontSize: vars.name.size * 1.25,
        }),
    );

    const label = style("label", {
        color: vars.label.color,
        textTransform: "uppercase",
        fontSize: vars.label.size,
        border: `1px solid ${vars.label.color}`,
        padding: vars.label.padding,
        borderRadius: vars.label.border,
    });

    const stat = style("stat", {
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        width: "49%",
    });

    const vertical = style("vertical", {
        display: "block",
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
        height: vars.header.height,
    });

    const section = style("section", {
        paddingTop: vars.container.margin * 0.5,
    });

    const email = style("email", {
        color: vars.email.color,
        $nest: linkColors.$nest,
    });

    const date = style("date", {
        padding: vars.buttonContainer.padding,
    });

    return {
        container,
        containerWithBorder,
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
