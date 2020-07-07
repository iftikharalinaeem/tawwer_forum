/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useThemeCache, variableFactory, styleFactory } from "@library/styles/styleUtils";
import { IThemeVariables } from "@library/theming/themeReducer";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import {
    borders,
    colorOut,
    EMPTY_BORDER,
    EMPTY_FONTS,
    EMPTY_SPACING,
    fonts,
    paddings,
    singleBorder,
    unit,
} from "@library/styles/styleHelpers";
import { clickableItemStates } from "@dashboard/compatibilityStyles/clickableItemHelpers";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { TextTransformProperty } from "csstype";
import { percent } from "csx";

export const userCardVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const makeVars = variableFactory("popupUserCard", forcedVars);
    const globalVars = globalVariables();
    const formElementsVars = formElementsVariables();

    const container = makeVars("card", {
        margin: globalVars.gutter.size,
    });

    const button = makeVars("button", {
        minWidth: 120,
        mobile: {
            minWidth: 140,
        },
    });

    const buttonContainer = makeVars("buttonContainer", {
        padding: globalVars.gutter.half,
    });

    const name = makeVars("name", {
        size: globalVars.fonts.size.large,
        weight: globalVars.fonts.weights.bold,
    });

    const label = makeVars("label", {
        border: {
            ...EMPTY_BORDER,
            color: globalVars.mainColors.primary,
            radius: 3,
        },
        padding: {
            ...EMPTY_SPACING,
            vertical: 2,
            horizontal: 10,
        },
        font: {
            ...EMPTY_FONTS,
            color: globalVars.mainColors.primary,
            size: 10,
            transform: "uppercase" as TextTransformProperty,
        },
    });

    const vertical = makeVars("vertical", {
        color: colorOut(globalVars.border.color),
    });

    const containerWithBorder = makeVars("containerWithBorder", {
        color: colorOut(globalVars.border.color),
        padding: globalVars.gutter.size,
    });

    const count = makeVars("count", {
        size: 28,
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
    const globalVars = globalVariables();

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

    const button = style(
        "button",
        {
            $nest: {
                "&&": {
                    minWidth: unit(vars.button.minWidth),
                },
            },
        },
        mediaQueries.oneColumnDown({
            $nest: {
                "&&": {
                    minWidth: unit(vars.button.mobile.minWidth),
                },
            },
        }),
    );

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
        ...fonts(vars.label.font),
        ...paddings(vars.label.padding),
        ...borders(vars.label.border),
    });

    const stat = style("stat", {
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        flexGrow: 1,
        maxWidth: percent(50),
        ...paddings({
            horizontal: globalVars.spacer.size,
        }),
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
        ...fonts({
            color: globalVars.mainColors.fg,
            size: globalVars.fonts.size.small,
        }),
        $nest: linkColors.$nest,
    });

    const date = style("date", {
        padding: vars.buttonContainer.padding,
    });

    const statLabel = style("statLabel", {
        ...fonts({
            size: globalVars.fonts.size.small,
        }),
    });

    const statLeft = style("statLeft", {
        borderRight: singleBorder({}),
    });
    const statRight = style("statRight", {});

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
        statLabel,
        statLeft,
        statRight,
    };
});
