/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { userPhotoVariables } from "@library/headers/mebox/pieces/userPhotoStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut, unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { IThemeVariables } from "@library/theming/themeReducer";
import { calc } from "csx";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { buttonResetMixin } from "@vanilla/library/src/scripts/forms/buttonStyles";
import { iconVariables } from "@vanilla/library/src/scripts/icons/iconStyles";

export const eventsVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const makeVars = variableFactory("eventParticipants", forcedVars);
    const globalVars = globalVariables();
    const iconVars = iconVariables();

    const tabsRoot = makeVars("tabsRoot", {
        fontSize: globalVars.fonts.size.large,
        size: {
            height: 552,
        },
    });

    const tabsList = makeVars("tabsList", {
        size: 45,
        fontWeight: globalVars.fonts.weights.semiBold,
        color: {
            borderBottom: colorOut(globalVars.border.color),
        },
    });

    const tabsPanels = makeVars("tabsPanels", {
        marginTop: tabsList.size,
    });

    const tabsTab = makeVars("tabsTab", {
        height: tabsList.size,
        border: {
            top: 3,
            bottom: 3,
        },
    });

    const item = makeVars("item", {
        marginBottom: 12,
    });

    const tabsTopButton = makeVars("tabsTopButton", {
        wrapper: {
            right: 0,
            top: 0,
        },
        size: {
            width: iconVars.standard.width,
            height: iconVars.standard.height,
        },
    });

    const tabsBottomButton = makeVars("tabsBottomButton", {
        wrapper: {
            bottom: 35,
        },
    });

    return {
        tabsRoot,
        tabsPanels,
        tabsList,
        tabsTab,
        item,
        tabsTopButton,
        tabsBottomButton,
    };
});

export const eventParticipantsClasses = useThemeCache((props: { compact?: boolean } = {}) => {
    const style = styleFactory("eventParticipants");
    const vars = eventsVariables();
    const globalVars = globalVariables();
    const mediaQueries = layoutVariables().mediaQueries();

    const tabsRoot = style("tabsRoot", {
        fontSize: vars.tabsRoot.fontSize,
        position: "relative",
        height: vars.tabsRoot.size.height,
    });

    const tabsList = style(
        "tabsList",
        {
            position: "fixed",
            top: 0,
            left: 0,
            right: 0,
            zIndex: 1,
            display: "flex",
            flexDirection: "row",
            alignItems: "center",
            backgroundColor: colorOut(globalVars.mainColors.bg),
            fontWeight: vars.tabsList.fontWeight,
            borderBottom: `solid 1px ${vars.tabsList.color.borderBottom}`,

            $nest: {
                "> *": {
                    marginLeft: globalVars.gutter.size * 3,
                },
                ":first-child": {
                    marginLeft: globalVars.gutter.size,
                },
            },
        },
        mediaQueries.xs({
            $nest: {
                "> *": {
                    marginLeft: globalVars.gutter.size * 1,
                },
            },
        }),
    );

    const tabsTab = style("tabsTab", {
        ...buttonResetMixin(),
        height: vars.tabsTab.height,
        borderTop: `3px solid ${colorOut(globalVars.elementaryColors.transparent)}`,
        borderBottom: `3px solid ${colorOut(globalVars.elementaryColors.transparent)}`,
        $nest: {
            "&[data-selected]": {
                borderBottom: `${vars.tabsTab.border.bottom}px solid ${colorOut(globalVars.mainColors.primary)}`,
            },
        },
    });

    const tabsPanels = style("tabsPanels", {
        marginTop: vars.tabsPanels.marginTop + globalVars.gutter.size,
    });

    const list = style("list", {
        marginLeft: globalVars.gutter.size,
        marginRight: globalVars.gutter.size,
    });

    const item = style("item", {
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
        marginBottom: vars.item.marginBottom,
        flexWrap: "nowrap",
        color: colorOut(globalVars.mainColors.fg),
    });

    const name = style("name", {
        display: "inline-flex",
        marginLeft: globalVars.gutter.size,
        width: calc(`100% - ${unit(userPhotoVariables().sizing.medium)}`),
        fontSize: globalVars.fonts.size.large,
    });

    const tabsTopButtonWrapper = style("tabsTopButtonWrapper", {
        position: "fixed",
        zIndex: 2,
        right: vars.tabsTopButton.wrapper.right,
        top: vars.tabsTopButton.wrapper.top,
        height: vars.tabsList.size,
        width: vars.tabsList.size,
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        justifyContent: "center",
    });

    const tabsTopButton = style("tabsTopButton", {
        display: "inline-flex",
        flexDirection: "column",
        alignItems: "center",
        justifyItems: "center",
        width: vars.tabsTopButton.size.width,
        height: vars.tabsTopButton.size.height,
    });

    const tabsBottomButtonWrapper = style("tabsBottomButton", {
        display: "flex",
        flexDirection: "row",
        justifyContent: "center",
        marginBottom: vars.tabsBottomButton.wrapper.bottom,
    });

    return {
        tabsRoot,
        tabsList,
        tabsPanels,
        item,
        list,
        name,
        tabsTab,
        tabsTopButtonWrapper,
        tabsTopButton,
        tabsBottomButtonWrapper,
    };
});
