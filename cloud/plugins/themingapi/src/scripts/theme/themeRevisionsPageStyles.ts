/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { unit, colorOut, margins, absolutePosition } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { globalVariables } from "@vanilla/library/src/scripts/styles/globalStyleVars";
import { metasVariables } from "@library/styles/metasStyles";
import { titleBarVariables } from "@library/headers/titleBarStyles";
import { calc, percent } from "csx";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { themeEditorVariables } from "@themingapi/theme/ThemeEditor.styles";

export const themeRevisionPageClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("themeEditorPage");
    const metasVars = metasVariables();
    const titleBarVars = titleBarVariables();
    const themeEditorVars = themeEditorVariables();

    const title = style("title", {
        fontWeight: globalVars.fonts.weights.bold,
        lineHeight: unit(16),
        textAlign: "center",
        ...margins({
            top: 12,
            bottom: 20,
        }),
    });

    const userNameFont = style("userNameFont", {
        fontWeight: globalVars.fonts.weights.semiBold,
        lineHeight: unit(14),
    });
    const labelMetas = style("labelMetas", {
        fontSize: unit(metasVars.fonts.size),
        color: colorOut(metasVars.colors.fg),
        lineHeight: unit(1),
    });

    const itemLabel = style("itemLabel", {
        flexGrow: 1,
        display: "block",
        paddingBottom: unit(10),
    });

    const itemLabelContainer = style("itemLabelContainer", {
        margin: unit(10),
        width: unit(250),
    });

    const revisionItem = style("revisionItem", {
        display: "flex",
        alignItems: "center",
        height: unit(55),
    });

    const padding = style("padding", {
        paddingLeft: unit(10),
    });

    const activeItem = style("activeItem", {
        color: colorOut(globalVars.mainColors.primary),
    });

    const mediaQueries = layoutVariables().mediaQueries();
    const frame = style(
        "frame",
        {
            top: unit(titleBarVars.sizing.height),
            position: "relative",
            flexBasis: calc(`${percent(themeEditorVars.frame.width)} - ${unit(themeEditorVars.panel.width)}`),
            height: percent(100),
        },

        mediaQueries.oneColumnDown({
            width: percent(100),
        }),
    );

    return {
        userNameFont,
        itemLabel,
        labelMetas,
        revisionItem,
        itemLabelContainer,
        title,
        padding,
        frame,
        activeItem,
    };
});
