/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { allLinkStates, colorOut, debugHelper, margins, unit, flexHelper, borders } from "@library/styles/styleHelpers";
import { useThemeCache } from "@library/styles/styleUtils";
import { px, percent, translateY } from "csx";
import { style } from "typestyle";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { buttonGlobalVariables } from "@library/forms/buttonStyles";
import { formElementsVariables } from "@library/forms/formElementStyles";

export const siteNavAdminLinksClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const debug = debugHelper("siteNavAdminLinks");
    const mediaQueries = layoutVariables().mediaQueries();
    const buttonVars = buttonGlobalVariables();
    const formVars = formElementsVariables();

    const root = style(
        {
            display: "block",
            ...margins({
                all: 0,
            }),
            transform: translateY(`${unit((buttonVars.sizing.minHeight - globalVars.icon.sizes.default) / -2)}`),
            ...debug.name(),
        },
        mediaQueries.oneColumnDown({
            transform: "none",
            ...margins({
                vertical: 16,
            }),
        }),
    );

    const item = style({
        display: "block",
        color: colorOut(globalVars.mainColors.fg),
        ...debug.name("item"),
    });

    const divider = style({
        ...borders({
            width: 1,
            bottom: { width: 0 },
        }),
        marginTop: px(16),
        marginBottom: px(16),
        ...debug.name("i"),
    });

    const link = style({
        minHeight: unit(buttonVars.sizing.minHeight),
        ...flexHelper().middleLeft(),
        padding: 0,
        width: percent(100),
        fontWeight: globalVars.fonts.weights.semiBold,
        ...allLinkStates({
            noState: {
                color: colorOut(globalVars.mainColors.fg),
            },
            hover: {
                color: colorOut(globalVars.mainColors.primary),
            },
            focus: {
                color: colorOut(globalVars.mainColors.secondary),
            },
        }),
        ...debug.name("link"),
    });

    const linkIcon = style({
        marginRight: px(6),
        width: px(formVars.sizing.halfHeight),
        height: px(formVars.sizing.halfHeight),
        ...debug.name("linkIcon"),
    });

    return { root, item, divider, link, linkIcon };
});
