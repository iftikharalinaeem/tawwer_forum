/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useThemeCache, styleFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { titleBarVariables } from "@library/headers/titleBarStyles";
import { unit, colorOut } from "@library/styles/styleHelpers";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { margins } from "@library/styles/styleHelpers";
import { em, percent, px } from "csx";

export const editorHeaderClasses = useThemeCache(() => {
    const style = styleFactory("editorHeader");
    const titleBarVars = titleBarVariables();
    const mediaQueries = layoutVariables().mediaQueries();
    const globalVars = globalVariables();

    const items = style(
        "items",
        {
            display: "flex",
            flexWrap: "nowrap",
            justifyContent: "flex-end",
            alignItems: "center",
            width: percent(100),
            height: unit(titleBarVars.sizing.height),
        },
        mediaQueries.oneColumnDown({
            height: unit(titleBarVars.sizing.mobile.height),
        }),
    );

    const item = style("item", {
        display: "inline-flex",
        alignItems: "center",
        justifyContent: "center",
        $nest: {
            "&.isPullLeft": {
                ...margins({
                    left: 0,
                    right: "auto",
                }),
                justifyContent: "flex-start",
            },
            "&.isPullRight": {
                ...margins({
                    left: "auto",
                    right: 0,
                }),
                justifyContent: "flex-end",
            },
            "&.editorHeader-itemDraftStatus": {
                marginLeft: 0,
            },
        },
    });

    const itemMarginLeft = style("itemMarginLeft", {
        marginLeft: unit(globalVars.gutter.half),
    });

    const centreColumn = style("centreColumn", {
        flexGrow: 1,
        ...margins({
            horizontal: unit(globalVars.spacer.size),
        }),
    });

    const saveDraft = style("saveDraft", {
        lineHeight: globalVars.lineHeights.condensed,
        marginTop: em(0.083),
    });

    const publish = style("publish", {
        color: colorOut(globalVars.mainColors.primary),
        fontWeight: globalVars.fonts.weights.semiBold,
        whiteSpace: "nowrap",
    });

    const otherLanguagesToggle = style("otherLanguagesToggle", {
        fontWeight: globalVars.fonts.weights.semiBold,
    });

    const split = style("split", {
        flexGrow: 1,
        height: px(1),
    });

    const backLink = style("backLink", {
        marginRight: "auto",
    });

    const backSpacer = style("backSpacer", {
        position: "relative",
        visibility: "hidden",
    });

    return {
        items,
        centreColumn,
        item,
        saveDraft,
        publish,
        otherLanguagesToggle,
        split,
        backLink,
        itemMarginLeft,
        backSpacer,
    };
});
