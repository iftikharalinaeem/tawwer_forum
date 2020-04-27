/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { unit, colorOut, margins } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { globalVariables } from "@vanilla/library/src/scripts/styles/globalStyleVars";
import { metasVariables } from "@library/styles/metasStyles";

export const themeRevisionPageClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("themeEditorPage");
    const vars = metasVariables();

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
        fontSize: unit(vars.fonts.size),
        color: colorOut(vars.colors.fg),
        lineHeight: unit(1),
    });

    const itemLabel = style("itemLabel", {
        flexGrow: 1,
        display: "block",
        paddingBottom: unit(10),
    });

    const itemLabelContainer = style("itemLabelContainer", {
        margin: "10px",
        width: "250px",
    });

    const revisionItem = style("revisionItem", {
        display: "flex",
        alignItems: "center",
        height: "55px",
    });

    return {
        userNameFont,
        itemLabel,
        labelMetas,
        revisionItem,
        itemLabelContainer,
        title,
    };
});
