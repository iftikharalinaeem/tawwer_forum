/**
\ * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { setAllLinkColors } from "@library/styles/styleHelpersLinks";
import { colorOut } from "@library/styles/styleHelpersColors";
import { paddings } from "@library/styles/styleHelpersSpacing";
import { borders, unit } from "@library/styles/styleHelpers";
import { percent } from "csx";

export const relatedArticlesClasses = useThemeCache(() => {
    const style = styleFactory("relatedArticles");
    const globalVars = globalVariables();

    const root = style({});

    const linkColors = setAllLinkColors();

    const linkList = style("linkList", {
        display: "flex",
        flexWrap: "wrap",
        ...paddings({
            horizontal: 8,
        }),
    });

    const linkItem = style("linkItem", {
        minWidth: 200,
        flexBasis: percent(50),
        flexGrow: 1,
    });

    const link = style("link", {
        display: "inline-block",
        ...paddings({
            vertical: globalVars.gutter.half,
        }),
        color: colorOut(globalVars.mainColors.fg),
        $nest: linkColors.nested,
    });

    const border = style("border", {
        ...borders({
            width: 1,
            bottom: { width: 0 },
        }),
        marginTop: unit(12),
        marginBottom: unit(35.5),
    });

    const header = style("header", {
        marginBottom: unit(12),
        ...paddings({
            horizontal: 8,
        }),
    });

    return { root, link, linkList, linkItem, border, header };
});
