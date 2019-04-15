/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useThemeCache, styleFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { vanillaHeaderVariables } from "@library/headers/vanillaHeaderStyles";
import { unit, colorOut } from "@library/styles/styleHelpers";
import { layoutVariables } from "@library/layout/layoutStyles";
import { margins } from "@library/styles/styleHelpers";
import { em, percent, px } from "csx";

export const editorHeaderClasses = useThemeCache(() => {
    const style = styleFactory("editorHeader");
    const headerVariables = vanillaHeaderVariables();
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
            height: unit(headerVariables.sizing.height),
        },
        mediaQueries.oneColumn({
            height: unit(headerVariables.sizing.mobile.height),
        }),
    );

    const item = style("item", {
        display: "inline-flex",
        alignItems: "center",
        justifyContent: "center",
        $nest: {
            "&.isPullLeft": margins({
                left: 0,
                right: "auto",
            }),
            "&.editorHeader-itemDraftStatus": {
                marginLeft: 0,
            },
        },
    });

    const itemPaddingLeft = style("itemPaddingLeft", {
        marginLeft: unit(globalVars.spacer.size),
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

    return {
        items,
        centreColumn,
        item,
        saveDraft,
        publish,
        otherLanguagesToggle,
        split,
        backLink,
        itemPaddingLeft,
    };
});
