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

    const compact = makeVars("compact", {
        gutter: globalVars.gutter.size,
    });

    const title = makeVars("title", {
        font: {
            lineHeight: globalVars.lineHeights.condensed,
            size: globalVars.fonts.size.large,
            weight: globalVars.fonts.weights.semiBold,
        },
        margin: 12,
    });

    const alignment = makeVars("alignment", {
        verticalCheat: 2,
    });

    const spacing = makeVars("spacing", {
        contentSpacer: globalVars.gutter.half - 4, // Cheated for alignment
        attendanceOffset: 5,
        padding: {
            vertical: 20,
            horizontal: 5,
        },
    });

    const attendanceStamp = makeVars("attendanceStamp", {
        border: {
            ...EMPTY_BORDER,
            radius: 2,
        } as IBorderStyles,
        font: {
            ...EMPTY_FONTS,
            color: globalVars.mixBgAndFg(0.7),
            size: 10,
            fontWeight: globalVars.fonts.weights.semiBold,
            transform: "uppercase",
        } as IFont,
        padding: {
            horizontal: 4,
            vertical: 2,
        },
        going: {
            fg: globalVars.mainColors.primary,
        },
    });

    const separator = makeVars("separator", {
        fg: globalVars.mixBgAndFg(0.2),
    });

    const section = makeVars("section", {
        title: {
            font: {
                ...EMPTY_FONTS,
                size: globalVars.fonts.size.medium,
                weight: globalVars.fonts.weights.bold,
            } as IFont,
        },
        spacing: {
            vertical: 20,
        },
    });

    const description = makeVars("description", {
        font: {
            ...EMPTY_FONTS,
            size: globalVars.fonts.size.medium,
        },
    });

    const attendees = makeVars("attendees", {
        offset: 15,
        plus: {
            font: {
                lineHeight: globalVars.lineHeights.condensed,
                size: globalVars.fonts.size.medium,
                weight: globalVars.fonts.weights.bold,
            },
            margin: 5,
        },
    });

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

    const participantsTabsPanels = makeVars("participantsTabsPanels", {
        marginTop: 50,
    });

    const participantsTabsList = makeVars("participantsTabsList", {
        height: 45,
        fontWeight: globalVars.fonts.weights.semiBold,
        color: {
            borderBottom: colorOut(globalVars.border.color),
        },
        marginBottom: 5,
    });

    const participantsTabsTab = makeVars("participantsTabsTab", {
        height: participantsTabsList.height - 2, //subtract the border heights
    });

    const participantItem = makeVars("participantItem", {
        marginBottom: 12,
    });

    const participantName = makeVars("participantName", {
        marginLeft: globalVars.gutter.size,
    });

    const participantsTabsTopButton = makeVars("participantsTabsTopButton", {
        wrapper: {
            right: 6,
            top: 10,
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
        compact,
        title,
        spacing,
        attendanceStamp,
        alignment,
        separator,
        section,
        description,
        attendees,
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

export const eventsClasses = useThemeCache((props: { compact?: boolean } = {}) => {
    const style = styleFactory("events");
    const vars = eventsVariables();
    const globalVars = globalVariables();
    const compactDateSize = dateTimeVariables().compact.container.size;
    const mediaQueries = layoutVariables().mediaQueries();

    const root = style("root", {
        display: "block",
    });

    const empty = style("empty", {
        display: "block",
    });

    const list = style("list", {
        display: "block",
        marginLeft: negativeUnit(vars.spacing.padding.horizontal * 2),
        width: calc(`100% + ${unit(vars.spacing.padding.horizontal * 4)}`),
    });

    const item = style("item", {
        display: "block",
        borderBottom: singleBorder(),
        $nest: {
            ["&&"]: {
                ...paddings({
                    vertical: important(0),
                    horizontal: vars.spacing.padding.horizontal,
                }),
            },
            [`&.isFirst`]: {
                borderTop: singleBorder(),
            },
        },
    });

    const title = style("title", {
        display: "block",
        ...fonts(vars.title.font),
        $nest: {
            "&&": margins({
                all: 0,
            }),
        },
    });

    const linkColors = clickableItemStates();
    const toggleClass = selectBoxClasses().toggle;

    const link = style(
        "link",
        {
            color: colorOut(globalVars.mainColors.fg),
            display: "flex",
            width: percent(100),
            flexWrap: "nowrap",
            flexGrow: 1,
            justifyContent: "flex-start",
            alignItems: "flex-start",
            ...paddings(vars.spacing.padding),
            $nest: {
                [`& .${toggleClass}`]: {
                    marginLeft: "auto",
                    fontWeight: globalVars.fonts.weights.normal,
                },
                [`&:hover .${title}`]: {
                    ...linkColors["$nest"]!["&&:hover"],
                },
                [`&:focus .${title}`]: {
                    ...linkColors["$nest"]!["&&:focus"],
                },
                [`&.focus-visible .${title}`]: {
                    ...linkColors["$nest"]!["&&:focus-visible"],
                },
                [`&:active .${title}`]: {
                    ...linkColors["$nest"]!["&&:active"],
                },
                [`&:visited .${title}`]: {
                    ...linkColors["$nest"]!["&&:visited"],
                },
            },
        },
        mediaQueries.oneColumnDown({
            maxWidth: important("100%"),
        }),
    );

    const linkAlignment = style("linkAlignment", {
        display: "flex",
        flexWrap: "nowrap",
        alignItems: "center",
        justifyContent: "flex-start",
        minHeight: unit(compactDateSize),
        flexGrow: 1,
    });

    const result = style("result", {
        display: "flex",
        flexWrap: "nowrap",
        justifyContent: "flex-start",
        alignItems: "flex-start",
        width: percent(100),
    });

    const dateCompact = style("dateCompact", {
        flexBasis: unit(compactDateSize),
        flexShrink: 1,
        alignSelf: "flex-start",
        color: colorOut(globalVars.mainColors.fg),
    });

    const body = style("body", {
        display: "block",
    });

    const main = (skipOffset?: boolean) => {
        const offset = skipOffset ? {} : lineHeightAdjustment();
        return style("main", {
            ...offset,
            display: "block",
            paddingLeft: unit(vars.compact.gutter),
            transform: translateY(`${unit(vars.alignment.verticalCheat)}`), // text alignment cheat
            maxWidth: calc(`100% - ${unit(compactDateSize)}`),
        });
    };

    const excerpt = style("excerpt", {
        display: "block",
        marginTop: unit(vars.spacing.contentSpacer),
        color: colorOut(globalVars.mainColors.fg),
    });

    const metas = style("metas", {
        // ...metaContainerStyles(),
        marginTop: unit(vars.spacing.contentSpacer),
    });

    const meta = style("meta", {
        display: "inline",
        ...fonts({
            size: globalVars.meta.text.fontSize,
            color: globalVars.meta.colors.fg,
            lineHeight: globalVars.lineHeights.meta,
        }),
        ...margins({
            right: globalVars.meta.spacing.default * 2,
        }),
    });

    const metaDate = style(
        "metaDate",
        {},
        mediaQueries.oneColumnDown({
            display: important("none"),
        }),
    );

    const metaAttendance = style(
        "metaAttendance",
        {},
        mediaQueries.aboveOneColumn({
            display: important("none"),
        }),
    );

    const attendance = style(
        "attendance",
        {
            display: "flex",
            alignItems: "center",
            justifyContent: "flex-end",
            ...paddings({
                vertical: vars.spacing.padding.vertical,
            }),
        },
        mediaQueries.oneColumnDown({
            display: "none",
        }),
    );

    const attendanceSelector = style("attendanceSelector", {
        display: "flex",
        justifyContent: "flex-start",
        alignItems: "center",
        flexWrap: "nowrap",
    });

    const dropDown = style("dropDown", {
        ...lineHeightAdjustment(),
        $nest: {
            [`& .${selectBoxClasses().toggle}`]: {
                marginLeft: "auto",
                fontWeight: globalVars.fonts.weights.normal,
            },
        },
    });

    const attendanceClass = (attendance: EventAttendance) => {
        return `eventAttendance-${camelCaseToDash(attendance)}`;
    };

    const attendanceVars = vars.attendanceStamp;
    const attendanceStamp = style("attendanceStamp", {
        $nest: {
            [`&&`]: {
                display: "inline-flex",
                ...margins({
                    left: "auto",
                }),
                ...fonts(attendanceVars.font),
                ...borders({
                    ...attendanceVars.border,
                    color: attendanceVars.border.color ?? attendanceVars.font.color, // default to font color. Darkenned because border is very thin and get anti-aliased
                }),
                ...paddings(attendanceVars.padding),
                whiteSpace: "nowrap",
                lineHeight: 1,
            },
            [`&.${attendanceClass(EventAttendance.GOING)}`]: {
                color: colorOut(attendanceVars.going.fg),
                borderColor: colorOut(attendanceVars.going.fg),
            },
            [`&.${meta}`]: {
                marginRight: globalVars.meta.spacing.default * 2,
            },
            [`&.${metaAttendance}`]: {
                display: "inline",
            },
        },
    });

    const viewMore = style("viewMore", {
        ...margins({
            top: globalVars.gutter.size,
        }),
        display: "block",
        textAlign: "right",
    });

    const filter = style("filter", {
        marginBottom: globalVars.gutter.half,
    });
    const filterLabel = style("filterLabel", {
        marginRight: globalVars.gutter.quarter,
    });
    const details = style("details", {});
    const separator = style("separator", {
        display: "block",
        width: percent(100),
        borderTop: 0,
        // Has to be a border and not a BG, because sometimes chrome rounds it's height to 0.99px and it disappears.
        borderBottom: singleBorder({
            color: vars.separator.fg,
        }),
        height: 0, // gets rid of default styles
        ...margins({
            bottom: vars.section.spacing.vertical,
        }),
    });
    // const attendanceAsRadio = style("attendanceAsRadio", {});

    const attendee = style("attendee", {
        width: unit(userPhotoVariables().sizing.medium - vars.attendees.offset),
        $nest: {
            "&.isLast": {
                width: "auto",
                marginRight: unit(vars.attendees.plus.margin),
            },
        },
    });

    const attendeeList = style("attendeeList", {
        display: "flex",
        flexWrap: "wrap",
        alignItems: "center",
        paddingRight: unit(vars.attendees.offset), // prevents photos from overflowing
    });

    const attendeePhoto = style("attendeePhoto", {
        // Intentionally not using borders() here to not mess up border radius;
        borderColor: colorOut(globalVars.mainColors.bg),
        borderWidth: unit(globalVars.border.width),
        borderStyle: "solid",
        backgroundColor: colorOut(globalVars.mainColors.bg),
        $nest: {
            [`& .${userPhotoClasses().photo}`]: {
                width: percent(100),
                height: percent(100),
            },
        },
    });

    const participantsPopUpButton = style("participantsPopUpButton", {
        display: "flex",
        alignItems: "center",
        flexDirection: "column",
        marginLeft: 20,
    });

    const attendeePlus = style("attendeePlus", {
        ...fonts(vars.attendees.plus.font),
        lineHeight: unit(userPhotoVariables().sizing.medium),
    });

    const noAttendees = style("noAttendees", {});

    const section = style("section", {
        ...margins(vars.section.spacing),
    });

    const firstSection = style("firstSection", {
        marginTop: -globalVars.spacer.size,
    });

    const pageTitle = style("pageTitle", {
        $nest: {
            "&&": {
                marginBottom: unit(globalVars.gutter.size),
            },
        },
    });

    const sectionTitle = style("sectionTitle", {
        ...fonts(vars.section.title.font),
        marginBottom: unit(vars.title.margin),
    });

    const description = style("description", {
        $nest: {
            "&&": {
                ...fonts(vars.description.font),
            },
        },
    });

    const organizer = style("organizer", {
        ...fonts({
            ...EMPTY_FONTS,
            color: ensureColorHelper(linkColors.color as string),
            lineHeight: globalVars.lineHeights.condensed,
        }),
    });

    const panelHeading = style("panelHeading", {
        ...margins({ bottom: globalVars.gutter.half }),
    });

    const participantsTabsRoot = style("participantsTabsRoot", {
        fontSize: vars.participantsTabsRoot.fontSize,
        position: "relative",
        width: vars.participantsTabsRoot.size.width,
        height: vars.participantsTabsRoot.size.height,
    });

    const participantsTabsList = style("participantsTabsList", {
        position: "fixed",
        top: 0,
        left: 0,
        right: 0,
        zIndex: 1,
        height: vars.participantsTabsList.height,
        backgroundColor: colorOut(globalVars.mainColors.bg),
        fontWeight: vars.participantsTabsList.fontWeight,
        borderBottom: `solid 1px ${vars.participantsTabsList.color.borderBottom}`,
        marginBottom: vars.participantsTabsList.marginBottom,
        $nest: {
            "> *": {
                paddingLeft: 0,
                paddingRight: 0,
                marginRight: globalVars.gutter.size * 3,
            },
            ":first-child": {
                marginLeft: globalVars.gutter.size,
            },
        },
    });

    const participantsTabsTab = style("participantsTabsTab", {
        ...buttonResetMixin(),
        // Note height and the border height add up to the height of the list
        height: vars.participantsTabsTab.height,
        $nest: {
            "&[data-selected]": {
                borderBottom: `3px solid ${colorOut(globalVars.mainColors.primary)}`,
            },
        },
    });

    const participantsTabsPanels = style("participantsTabsPanels", {
        marginTop: vars.participantsTabsPanels.marginTop,
    });

    const participantList = style("participantsList", {
        marginLeft: globalVars.gutter.size,
    });

    const participantItem = style("participantItem", {
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
        marginBottom: vars.participantItem.marginBottom,
    });

    const participantName = style("participantName", {
        display: "inline-block",
        marginLeft: vars.participantName.marginLeft,
    });

    const participantsTabsTopButtonWrapper = style("participantsTabsTopButtonWrapper", {
        position: "fixed",
        zIndex: 2,
        right: vars.participantsTabsTopButton.wrapper.right,
        top: vars.participantsTabsTopButton.wrapper.top,
    });

    const participantsTabsTopButton = style("participantsTabsTopButton", {
        display: "inline-flex",
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
        root,
        item,
        list,
        body,
        result,
        link,
        linkAlignment,
        title,
        main,
        excerpt,
        metas,
        meta,
        metaDate,
        metaAttendance,
        empty,
        attendance,
        dateCompact,
        dropDown,
        attendanceClass,
        attendanceStamp,
        attendanceSelector,
        viewMore,
        filter,
        filterLabel,
        details,
        separator,
        attendee,
        attendeeList,
        attendeePhoto,
        participantsPopUpButton,
        attendeePlus,
        noAttendees,
        section,
        firstSection,
        pageTitle,
        sectionTitle,
        description,
        organizer,
        panelHeading,
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
