/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { borders, colorOut, margins, paddings, unit } from "@library/styles/styleHelpers";
import { important, percent, color, calc } from "csx";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { buttonStates } from "@library/styles/styleHelpers";
import { userSelect } from "@library/styles/styleHelpers";
import { allButtonStates } from "@library/styles/styleHelpers";
import { layoutVariables } from "@library/layout/layoutStyles";
import { formElementsVariables } from "@library/forms/formElementStyles";

export const navigationManagerVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const formElementVars = formElementsVariables();
    const makeThemeVars = variableFactory("navigationManager");

    const colors = makeThemeVars("colors", {
        fg: globalVars.mainColors.primary,
    });

    const dragging = makeThemeVars("dragging", {
        lineHeight: 18,
        border: {
            radius: 2,
            color: globalVars.mixBgAndFg(0.9),
        },
        bg: color("#E5EFFB"),
        scrollGutter: {
            mobile: globalVars.gutter.size * 2,
        },
    });

    const error = makeThemeVars("error", {
        color: globalVars.feedbackColors.error,
    });

    const item = makeThemeVars("item", {
        height: 28,
        mobileHeight: formElementVars.sizing.height,
    });

    const deleteButton = makeThemeVars("deleteButton", {
        color: globalVars.feedbackColors.error,
    });

    const actionButton = makeThemeVars("actionButton", {
        shadowColor: globalVars.mainColors.bg,
    });

    const renameButton = makeThemeVars("renameButton", {
        disabled: {
            fg: globalVars.mixBgAndFg(0.7),
        },
    });

    const folderIcon = makeThemeVars("folderIcon", {
        width: 19,
        height: 14,
        fg: globalVars.mixBgAndFg(0.5),
    });

    const chevron = makeThemeVars("chevron", {
        margin: 8,
        width: 8,
    });

    const input = makeThemeVars("input", {
        lineHeight: 24,
    });

    return {
        colors,
        dragging,
        error,
        item,
        deleteButton,
        actionButton,
        renameButton,
        folderIcon,
        chevron,
        input,
    };
});

export const navigationManagerClasses = useThemeCache(() => {
    const vars = navigationManagerVariables();
    const globalVars = globalVariables();
    const style = styleFactory("navigationManager");
    const chevronFullWidth = vars.chevron.width + 2 * vars.chevron.margin;
    const buttonWidth = chevronFullWidth + vars.folderIcon.width;
    const shadows = shadowHelper();
    const media = layoutVariables().mediaQueries();

    const root = style({
        $nest: {
            "& > [data-react-beautiful-dnd-droppable]": {
                paddingBottom: unit(50),
                marginLeft: unit(-chevronFullWidth),
                ...media.oneColumn({
                    marginLeft: unit(-chevronFullWidth / 2),
                }),
            },
            ...media.oneColumn({
                paddingRight: unit(vars.dragging.scrollGutter.mobile),
            }).$nest,
        },
    });

    const container = style("container", {
        paddingTop: globalVars.gutter.size,
        position: "relative",
        maxWidth: unit(800),
        width: percent(100),
        ...margins({ horizontal: "auto" }),
    });

    const formError = style("formError", {
        position: "absolute",
        top: unit(globalVars.gutter.half),
        left: 0,
        right: 0,
    });

    const header = style("header", {
        ...paddings({
            left: unit(globalVars.gutter.half),
            right: unit(globalVars.gutter.half),
        }),
    });

    const item = style("item", {
        border: important(0),
        outline: important("none"),
        $nest: {
            ...media.oneColumn({
                width: calc(`100% + ${unit(globalVars.gutter.size)}`),
            }).$nest,
            "&.isDragging": {
                minWidth: unit(300),
                $nest: {
                    "& .navigationManager-draggable": {
                        ...shadows.embed(),
                        ...borders(vars.dragging.border),
                        backgroundColor: colorOut(globalVars.mainColors.bg),
                    },
                },
            },
            "&:hover .navigationManager-draggable": {
                backgroundColor: colorOut(vars.dragging.bg.fade(0.4)),
            },

            "&.isActive .navigationManager-draggable": {
                backgroundColor: colorOut(vars.dragging.bg),
                ...borders(vars.dragging.border),
            },
            "&.isActive .navigationManager-action": {
                display: "block",
            },

            "&:focus .navigationManager-draggable": {
                backgroundColor: colorOut(vars.dragging.bg),
                ...borders(vars.dragging.border),
            },
            "&:focus .navigationManager-action": {
                display: "block",
            },

            "&.hasError .navigationManager-itemLabel": {
                color: colorOut(globalVars.feedbackColors.error.fg),
            },
        },
    });

    const draggable = style("draggable", {
        position: "relative",
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
        minHeight: unit(vars.item.height),
        lineHeight: unit(vars.dragging.lineHeight),
        fontSize: unit(globalVars.fonts.size.medium),
        backgroundColor: colorOut(globalVars.mainColors.bg),
        ...media.oneColumn({
            minHeight: unit(vars.item.mobileHeight),
        }),
        ...borders({
            color: "transparent",
        }),
        marginLeft: unit(18),
    });

    const action = style("action", {
        fontSize: unit(globalVars.fonts.size.medium),
        fontWeight: globalVars.fonts.weights.bold,
        minHeight: unit(vars.item.height),
        ...paddings({
            top: 0,
            right: 9,
            bottom: 0,
            left: 9,
        }),
        display: "none",
        textShadow: `${colorOut(vars.actionButton.shadowColor)} 0 0 2px`,
        whiteSpace: "nowrap",
        ...borders({
            color: "transparent",
        }),
        ...allButtonStates({
            hover: {
                color: colorOut(globalVars.mainColors.primary),
            },
            focus: {
                color: colorOut(globalVars.mainColors.primary),
            },
            active: {
                color: colorOut(globalVars.mainColors.primary),
            },
            focusNotKeyboard: {
                outline: 0,
            },
        }),
    });

    const deleteItem = style("deleteItem", {
        color: colorOut(vars.deleteButton.color.fg),
    });

    const submitRename = style("submitRename", {
        $nest: {
            "&[disabled]": {
                cursor: "not-allowed",
                color: colorOut(vars.renameButton.disabled.fg),
            },
        },
    });

    const input = style("input", {
        ...borders({
            color: "transparent",
            radius: 0,
            width: 1,
        }),
        ...paddings({
            vertical: 0,
            horizontal: 5,
        }),
        lineHeight: unit(vars.input.lineHeight),
        outline: 0,
        $nest: {
            "&.isFolder": {
                fontWeight: globalVars.fonts.weights.semiBold,
            },
            "&:focus": {
                borderColor: colorOut(globalVars.mainColors.primary.fade(0.5)),
            },
        },
    });
    const itemLabel = style("itemLabel", {
        flex: 1,
        width: percent(100),
        ...paddings({
            vertical: 3,
            horizontal: 6,
        }),
        $nest: {
            "&.isFolder": {
                fontWeight: globalVars.fonts.weights.semiBold,
            },
        },
    });

    const itemIcon = style("itemIcon", {
        color: "inherit",
    });

    const articleIcon = style("articleIcon", {
        display: "block",
        margin: "auto",
    });

    const articleIconFill = style("articleIconFill", {
        fill: colorOut(globalVars.mainColors.bg),
    });

    const toggleFolder = style("toggleFolder", {
        display: "flex",
        position: "relative",
        alignItems: "center",
        height: unit(vars.item.height),
        width: unit(buttonWidth),
        flexBasis: unit(buttonWidth),
        padding: 0,
    });

    const articlePage = style("articlePage", {
        display: "flex",
        alignItems: "center",
        width: unit(buttonWidth),
        minHeight: unit(vars.item.height),
    });

    const toggleSpacer = style("toggleSpacer", {
        height: unit(globalVars.icon.sizes.default),
        width: unit(chevronFullWidth),
    });

    const triangle = style("triangle", {
        width: unit(vars.chevron.width),
        ...margins({
            top: 0,
            right: vars.chevron.width,
            bottom: 0,
            left: vars.chevron.width,
        }),
    });

    const folder = style("folder", {
        display: "block",
        color: colorOut(vars.folderIcon.fg),
        margin: "auto",
    });

    const folderIcon = style("folderIcon", {
        width: unit(vars.folderIcon.width),
        height: unit(vars.folderIcon.height),
    });

    const editMode = style("editMode", {
        display: "flex",
        alignItems: "center",
        flexGrow: 1,
    });

    const text = style("text", {
        flexGrow: 1,
    });

    const button = style("button", {
        ...userSelect(),
        border: important(0),
        ...paddings({ horizontal: 12 }),
        $nest: {
            ...buttonStates({
                focusNotKeyboard: {
                    outline: 0,
                },
                allStates: {
                    color: colorOut(globalVars.mainColors.primary),
                },
                noState: {
                    color: colorOut(globalVars.mainColors.fg),
                },
            }),
        },
    });

    return {
        root,
        container,
        formError,
        header,
        item,
        draggable,
        action,
        submitRename,
        deleteItem,
        input,
        itemLabel,
        itemIcon,
        articleIcon,
        articleIconFill,
        toggleFolder,
        articlePage,
        toggleSpacer,
        triangle,
        folder,
        folderIcon,
        editMode,
        text,
        button,
    };
});
