/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { calc, percent, px, viewHeight } from "csx";
import { cssRule, media } from "typestyle";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { margins, paddings, unit } from "@library/styles/styleHelpers";
import { important } from "csx/lib/strings";
import { lineHeightAdjustment } from "@library/styles/textUtils";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import {
    allLinkStates,
    borders,
    colorOut,
    objectFitWithFallback,
    singleBorder,
    userSelect,
} from "@library/styles/styleHelpers";
import { NestedCSSProperties, NestedCSSSelectors } from "typestyle/lib/types";

export const itemListVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("layoutVariables");

    // Important variables that will be used to calculate other variables
    const avatar = makeThemeVars("avatar", {
        size: 30,
        margin: 9,
        border: {
            width: globalVars.border.width,
            radius: percent(50),
            color: globalVars.mixBgAndFg(0.1),
        },
    });

    const status = makeThemeVars("status", {
        color: globalVars.mixBgAndFg(0.5),
        size: globalVars.icon.sizes.default,
    });

    const border = makeThemeVars("border", globalVars.border);

    const spacing = makeThemeVars("spacing", {
        default: 16,
    });

    const item = makeThemeVars("item", {
        minHeight: 55,
    });

    const user = makeThemeVars("user", {
        fontSize: 16,
    });

    const link = makeThemeVars("link", {
        mobile: {
            padding: 16,
        },
    });

    return {
        avatar,
        status,
        border,
        spacing,
        item,
        user,
        link,
    };
});

export const itemListClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = itemListVariables();
    const mediaQueries = layoutVariables().mediaQueries();
    const style = styleFactory("itemList");

    const items = style("items", {
        display: "block",
    });

    const item = style("item", {
        display: "block",
        $nest: {
            "&:hover": {
                background: colorOut(globalVars.mainColors.primary.fade(0.05)),
            },
        },
    });

    const dateTime = style("dateTime", {
        display: "block",
    });

    const root = style(
        {
            ...paddings({
                vertical: globalVars.gutter.half,
            }),
            $nest: {
                [`.${item} .${item}`]: {
                    borderTop: singleBorder(vars.border),
                },
            },
        },
        mediaQueries.oneColumnDown({
            ...paddings({ vertical: 0 }),
        }),
    );

    const link = style(
        "link",
        {
            ...userSelect(),
            display: "flex",
            flexWrap: "nowrap",
            justifyContent: "flex-start",
            alignItems: "center",
            minHeight: unit(vars.item.minHeight),
            ...paddings({
                vertical: 0,
                horizontal: globalVars.gutter.quarter,
            }),
            ...allLinkStates({
                allStates: {
                    textDecoration: important("none"),
                    color: "inherit",
                },
            }),
        },
        mediaQueries.oneColumnDown({
            ...paddings({
                horizontal: vars.link.mobile.padding,
            }),
        }),
    );

    const photoFrame = style("photoFrame", {
        ...borders(vars.avatar.border),
        position: "relative",
        width: unit(vars.avatar.size + vars.avatar.border.width * 2),
        height: unit(vars.avatar.size + vars.avatar.border.width * 2),
        marginRight: unit(vars.avatar.margin),
        overflow: "hidden",
    } as NestedCSSProperties);

    const content = style("content", {
        display: "flex",
        flexDirection: "column",
        justifyContent: "space-between",
        alignItems: "flex-start",
        flexGrow: 1,
        ...paddings({
            vertical: 8,
            horizontal: 0,
        }),
        minWidth: calc(
            `100% - ${unit(
                vars.avatar.size + vars.avatar.border.width * 2 + vars.avatar.margin + vars.spacing.default * 2,
            )}`,
        ),
    });

    const photo = style("photo", {
        ...objectFitWithFallback(),
        minWidth: percent(100),
        minHeight: percent(100),
    });

    const userName = style("userName", {
        textDecoration: "none",
        color: "inherit",
        fontSize: unit(globalVars.userContent.font.sizes.default),
        fontWeight: globalVars.fonts.weights.semiBold,
        $nest: lineHeightAdjustment(),
    });

    const status = style("status", {
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        height: unit(vars.status.size),
        width: unit(vars.status.size),
        color: colorOut(vars.status.color),
        $nest: {
            "&.status-published": {
                color: colorOut(globalVars.mainColors.primary),
            },
        },
    });

    const icon = style("icon", {
        display: "block",
        margin: "auto",
    });

    const title = style({
        ...paddings({ horizontal: unit(globalVars.gutter.quarter) }),
    });

    return {
        root,
        link,
        title,
        photoFrame,
        content,
        photo,
        userName,
        status,
        icon,
        items,
        item,
        dateTime,
    };
});
