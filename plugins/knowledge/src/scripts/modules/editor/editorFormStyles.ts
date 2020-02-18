/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { formElementsVariables } from "@library/forms/formElementStyles";
import { titleBarVariables } from "@library/headers/titleBarStyles";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import {
    absolutePosition,
    colorOut,
    margins,
    negative,
    paddings,
    placeholderStyles,
    pointerEvents,
    sticky,
    unit,
} from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { richEditorVariables } from "@rich-editor/editor/richEditorVariables";
import { calc, percent, px } from "csx";
import { NestedCSSProperties } from "typestyle/lib/types";
import { textInputSizingFromSpacing } from "@library/styles/styleHelpers";
import { richEditorClasses } from "@rich-editor/editor/richEditorStyles";

export const editorFormClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const titleBarVars = titleBarVariables();
    const layoutVars = layoutVariables();
    const vars = richEditorVariables();
    const formElementVars = formElementsVariables();
    const style = styleFactory("editorFormClasses");
    const classesRichEditor = richEditorClasses(false);
    const mediaQueries = layoutVars.mediaQueries();

    const hasError = style("hasError", {});

    const root = style({
        position: "fixed",
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        height: percent(100),
        maxHeight: percent(100),
        overflow: "auto",
    });

    const spacer = style("spacer", {
        display: "block",
        height: layoutVars.gutter.size,
        minHeight: layoutVars.gutter.size,
        width: percent(100),
    });

    const title = style("title", {
        $nest: {
            "&.inputText, &&": {
                ...textInputSizingFromSpacing(vars.title.fontSize, 0, formElementVars.border.width * 2),
                color: colorOut(formElementVars.colors.fg),
                backgroundColor: colorOut(formElementVars.colors.bg),
                position: "relative",
                fontWeight: globalVars.fonts.weights.semiBold,
                borderRadius: 0,
                borderTop: 0,
                borderRight: 0,
                borderLeft: 0,
                borderBottom: `solid transparent 2px`,
                marginBottom: unit(globalVars.spacer.size),
                ...paddings({
                    left: 0,
                    right: 0,
                }),
            },
            "&:not(.focus-visible)": {
                outline: "none",
            },
            "&:focus": {
                borderBottomColor: colorOut(globalVars.mainColors.primary),
            },
            ...placeholderStyles({
                lineHeight: "inherit",
                padding: "inherit",
                color: colorOut(formElementVars.placeholder.color),
            }),
            [`&.${hasError}`]: {
                marginBottom: 0,
            },
        },
    });

    const mobileGutter = layoutVars.gutter.halfSize + 4; // 4 is from panel widget. Not yet converted,;

    const containerWidth = style(
        "containerWidth",
        {
            width: percent(100),
            maxWidth: layoutVars.middleColumn.paddedWidth,
        },
        paddings({
            horizontal: globalVars.gutter.size,
        }),
        margins({
            horizontal: "auto",
        }),
        mediaQueries.oneColumnDown({
            ...paddings({
                horizontal: mobileGutter,
            }),
        }),
    );

    const modernFrame = style("modernFrame", {
        position: "relative",
        ...paddings({
            vertical: vars.modernFrame.padding,
        }),
    });

    const conversionNotice = style("conversionNotice", {
        ...margins({
            top: unit(vars.modernFrame.margin),
        }),
        ...paddings({
            horizontal: 0,
        }),
    });

    const editor = (topWindowPosition: number) =>
        style("editor", {
            display: "flex",
            flexDirection: "column",
            borderTopLeftRadius: 0,
            borderTopRightRadius: 0,
            marginTop: unit(-formElementVars.border.width),
            minHeight: calc(`100vh - ${px(topWindowPosition)}`),
        });

    const embedBarContainer = style("embedBarContainer", sticky(), {
        top: titleBarVars.sizing.height,
        zIndex: 2,
        background: colorOut(vars.colors.bg),
    });

    const header = style("header", sticky(), {
        top: 0,
        zIndex: 3,
    });

    const embedBarMixin: NestedCSSProperties = {
        position: "absolute",
        transform: `translateX(-50%)`,
        height: globalVars.separator.size,
        background: colorOut(globalVars.separator.color),
    };

    const embedBarTop = style("embedBarTop", embedBarMixin, {
        top: 0,
        left: percent(50),
        width: percent(100),
    });

    const embedBarBottom = style(
        "containerInset",
        embedBarMixin,
        {
            top: percent(100),
            left: percent(50),
            width: layoutVars.middleColumn.width + globalVars.gutter.half + globalVars.spacer.size,
        },
        mediaQueries.oneColumnDown({
            width: calc(`100% - ${unit(mobileGutter)}`),
        }),
    );

    const titleErrorMessage = style("titleErrorMessage", {
        minHeight: unit(globalVars.spacer.size),
        ...paddings({
            bottom: unit(6),
        }),
    });

    const bodyErrorWrap = style("bodyErrorWrap", {
        position: "relative",
    });

    const bodyErrorMessage = style("bodyErrorMessage", {
        ...pointerEvents(),
        ...absolutePosition.topLeft(globalVars.fonts.size.large * globalVars.lineHeights.base + 19),
    });

    const categoryErrorParagraph = style("categoryErrorParagraph", {
        ...margins({
            vertical: 8,
        }),
    });

    const titleErrorParagraph = style("titleErrorParagraph", {
        lineHeight: unit(globalVars.lineHeights.base * globalVars.fonts.size.large + 2),
    });

    const publish = style("publish", {
        whiteSpace: "nowrap",
    });

    return {
        root,
        spacer,
        title,
        editor,
        header,
        embedBarContainer,
        bodyErrorMessage,
        bodyErrorWrap,
        containerWidth,
        conversionNotice,
        modernFrame,
        embedBarTop,
        embedBarBottom,
        titleErrorMessage,
        categoryErrorParagraph,
        titleErrorParagraph,
        publish,
        hasError,
    };
});
