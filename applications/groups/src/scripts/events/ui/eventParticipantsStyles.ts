/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { camelCaseToDash } from "@dashboard/compatibilityStyles";
import { clickableItemStates } from "@dashboard/compatibilityStyles/clickableItemHelpers";
import { dateTimeVariables } from "@library/content/dateTimeStyles";
import { selectBoxClasses } from "@library/forms/select/selectBoxStyles";
import { userPhotoClasses, userPhotoVariables } from "@library/headers/mebox/pieces/userPhotoStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import {
    colorOut,
    EMPTY_FONTS,
    ensureColorHelper,
    fonts,
    IFont,
    negativeUnit,
    unit,
} from "@library/styles/styleHelpers";
import { borders, EMPTY_BORDER, IBorderStyles, singleBorder } from "@library/styles/styleHelpersBorders";
import { margins, paddings } from "@library/styles/styleHelpersSpacing";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { lineHeightAdjustment } from "@library/styles/textUtils";
import { IThemeVariables } from "@library/theming/themeReducer";
import { calc, important, percent, translateY, color } from "csx";
import { EventAttendance } from "@groups/events/state/eventsTypes";
import { textLinkCSS } from "@dashboard/compatibilityStyles/textLinkStyles";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { media } from "typestyle";
import { userSelect } from "@library/styles/styleHelpers";
import { buttonResetMixin } from "@vanilla/library/src/scripts/forms/buttonStyles";
import { iconVariables } from "@vanilla/library/src/scripts/icons/iconStyles";

export const eventsVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const makeVars = variableFactory("dateTime", forcedVars);
    const globalVars = globalVariables();
    const iconVars = iconVariables();

    const participantsTabsRoot = makeVars("participantsTabsRoot", {
        fontSize: globalVars.fonts.size.large,
        size: {
            width: 516,
            height: 552,
        },
        color: {
            border: colorOut(globalVars.border.color),
        },
    });

    const participantsTabsList = makeVars("participantsTabsList", {
        height: 45,
        // bottom: 3,
        fontWeight: globalVars.fonts.weights.semiBold,
        color: {
            borderBottom: colorOut(globalVars.border.color),
        },
        // marginBottom: 10,
    });

    const participantsTabsPanels = makeVars("participantsTabsPanels", {
        marginTop: participantsTabsList.height,
    });

    const participantsTabsTab = makeVars("participantsTabsTab", {
        height: participantsTabsList.height, //- participantsTabsList.bottom, //subtract the border heights
        border: {
            top: 3,
            bottom: 3,
        },
    });

    const participantItem = makeVars("participantItem", {
        marginBottom: 12,
    });

    const participantName = makeVars("participantName", {
        marginLeft: globalVars.gutter.size,
    });

    const participantsTabsTopButton = makeVars("participantsTabsTopButton", {
        wrapper: {
            right: 0,
            top: 0,
        },
        size: {
            width: iconVars.standard.width,
            height: iconVars.standard.height,
        },
    });

    const participantsTabsBottomButton = makeVars("participantsTabsBottomButton", {
        wrapper: {
            bottom: 35,
        },
    });

    return {
        participantsTabsRoot,
        participantsTabsPanels,
        participantsTabsList,
        participantsTabsTab,
        participantItem,
        participantName,
        participantsTabsTopButton,
        participantsTabsBottomButton,
    };
});

export const eventParticipantsClasses = useThemeCache((props: { compact?: boolean } = {}) => {
    const style = styleFactory("events");
    const vars = eventsVariables();
    const globalVars = globalVariables();
    const compactDateSize = dateTimeVariables().compact.container.size;
    const mediaQueries = layoutVariables().mediaQueries();

    const participantsPopUpButton = style("participantsPopUpButton", {
        display: "flex",
        alignItems: "center",
        flexDirection: "column",
        marginLeft: 20,
    });

    const participantsTabsRoot = style("participantsTabsRoot", {
        fontSize: vars.participantsTabsRoot.fontSize,
        position: "relative",
        // width: vars.participantsTabsRoot.size.width,
        height: vars.participantsTabsRoot.size.height,
    });

    const participantsTabsList = style(
        "participantsTabsList",
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
            fontWeight: vars.participantsTabsList.fontWeight,
            borderBottom: `solid 1px ${vars.participantsTabsList.color.borderBottom}`,

            // marginBottom: vars.participantsTabsList.marginBottom,
            $nest: {
                "> *": {
                    // paddingLeft: 0,
                    // paddingRight: 0,
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

    const participantsTabsTab = style("participantsTabsTab", {
        ...buttonResetMixin(),
        // Note height and the border height add up to the height of the list
        // backgroundColor: "red",
        height: vars.participantsTabsTab.height,
        borderTop: `3px solid ${colorOut(globalVars.elementaryColors.transparent)}`,
        // borderTop: "3px solid red",
        borderBottom: `3px solid ${colorOut(globalVars.elementaryColors.transparent)}`,
        $nest: {
            "&[data-selected]": {
                borderBottom: `${vars.participantsTabsTab.border.bottom}px solid ${colorOut(
                    globalVars.mainColors.primary,
                )}`,
            },
        },
    });

    const participantsTabsPanels = style("participantsTabsPanels", {
        marginTop: vars.participantsTabsPanels.marginTop + globalVars.gutter.size,
        // backgroundColor: "green",
    });

    const participantList = style("participantsList", {
        marginLeft: globalVars.gutter.size,
        marginRight: globalVars.gutter.size,
    });

    const participantItem = style("participantItem", {
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
        marginBottom: vars.participantItem.marginBottom,
        // backgroundColor: "pink",
        flexWrap: "nowrap",
    });

    const participantName = style("participantName", {
        display: "inline-flex",
        marginLeft: globalVars.gutter.size,
        width: calc(`100% - ${unit(userPhotoVariables().sizing.medium)}`),
    });

    const participantsTabsTopButtonWrapper = style("participantsTabsTopButtonWrapper", {
        position: "fixed",
        zIndex: 2,
        right: vars.participantsTabsTopButton.wrapper.right,
        top: vars.participantsTabsTopButton.wrapper.top,
        // backgroundColor: "red",
        height: vars.participantsTabsList.height, //- vars.participantsTabsList.bottom,
        width: vars.participantsTabsList.height,
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        justifyContent: "center",
    });

    const participantsTabsTopButton = style("participantsTabsTopButton", {
        display: "inline-flex",
        flexDirection: "column",
        alignItems: "center",
        justifyItems: "center",
        width: vars.participantsTabsTopButton.size.width,
        height: vars.participantsTabsTopButton.size.height,
    });

    const participantsTabsBottomButtonWrapper = style("participantsTabsBottomButton", {
        display: "flex",
        flexDirection: "row",
        justifyContent: "center",
        marginBottom: vars.participantsTabsBottomButton.wrapper.bottom,
    });

    return {
        participantsTabsRoot,
        participantsTabsList,
        participantsTabsPanels,
        participantItem,
        participantList,
        participantName,
        participantsTabsTab,
        participantsTabsTopButtonWrapper,
        participantsTabsTopButton,
        participantsTabsBottomButtonWrapper,
    };
});
