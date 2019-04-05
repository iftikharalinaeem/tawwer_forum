/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { formElementsVariables } from "@library/forms/formElementStyles";
import { vanillaHeaderVariables } from "@library/headers/vanillaHeaderStyles";
import { layoutVariables } from "@library/layout/layoutStyles";
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
    textInputSizing,
    unit,
} from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { richEditorVariables } from "@rich-editor/editor/richEditorVariables";
import { calc, percent, px, viewHeight } from "csx";
import { NestedCSSProperties } from "typestyle/lib/types";

export const editorFormClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const headerVars = vanillaHeaderVariables();
    const layoutVars = layoutVariables();
    const vars = richEditorVariables();
    const formElementVars = formElementsVariables();
    const style = styleFactory("richEditorForm");
    const root = style({
        height: viewHeight(100),
        maxHeight: viewHeight(100),
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
                ...textInputSizing(
                    vars.title.height,
                    vars.title.fontSize,
                    globalVars.gutter.half,
                    formElementVars.border.fullWidth,
                ),
                color: colorOut(formElementVars.colors.fg),
                backgroundColor: colorOut(formElementVars.colors.bg),
                position: "relative",
                fontWeight: globalVars.fonts.weights.semiBold,
                border: 0,
                borderRadius: 0,
                marginBottom: unit(globalVars.spacer.size),
                ...paddings({
                    left: 0,
                    right: 0,
                }),
            },
            "&:not(.focus-visible)": {
                outline: "none",
            },
            ...placeholderStyles({
                lineHeight: "inherit",
                padding: "inherit",
                color: colorOut(formElementVars.placeholder.color),
            }),
        },
    });

    const desktopGutter = layoutVars.gutter.size + layoutVars.gutter.halfSize;
    const mobileGutter = layoutVars.gutter.halfSize + 4; // 4 is from panel widget. Not yet converted,;

    const containerWidth = style(
        "containerWidth",
        {
            width: percent(100),
            maxWidth: layoutVars.middleColumnWidth,
        },
        paddings({
            horizontal: desktopGutter,
        }),
        layoutVariables()
            .mediaQueries()
            .oneColumn({
                ...paddings({
                    horizontal: mobileGutter,
                }),
            }),
        margins({
            horizontal: "auto",
        }),
    );

    const modernFrame = style("modernFrame", {
        position: "relative",
        ...paddings({
            top: globalVars.gutter.half,
            bottom: globalVars.gutter.size,
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
        // borderBottom: `${unit(formElementVars.border.width)} solid ${colorOut(formElementVars.border.color)}`,
        top: headerVars.sizing.height,
        zIndex: 2,
        background: colorOut(vars.colors.bg),
    });

    const header = style("header", sticky(), {
        top: 0,
        zIndex: 2,
    });

    const embedBar = style("embedBar", {});

    const embedBarMixin: NestedCSSProperties = {
        position: "absolute",
        transform: `translateX(-50%)`,
        height: globalVars.separator.size,
        background: colorOut(globalVars.separator.color),
    };

    const embedBarBottomFull = style("embedBarBottom", embedBarMixin, {
        top: percent(100),
        left: percent(50),
        width: percent(100),
    });
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
            width: layoutVars.middleColumnWidth - desktopGutter * 2,
        },
        layoutVariables()
            .mediaQueries()
            .oneColumn({
                width: calc(`100% - ${mobileGutter * 2}px`),
            }),
    );

    const bodyErrorMessage = style("bodyErrorMessage", {
        ...absolutePosition.topLeft(),
    });

    const titleErrorMessage = style("titleErrorMessage", {
        ...pointerEvents(),
        ...margins({
            top: unit(negative(globalVars.spacer.size)),
            bottom: globalVars.spacer.size,
        }),
    });

    const categoryErrorParagraph = style("categoryErrorParagraph", {
        ...margins({
            vertical: 8,
        }),
    });

    const titleErrorParagraph = style("titleErrorParagraph", {
        lineHeight: unit(globalVars.lineHeights.base * globalVars.fonts.size.large + 2),
    });

    return {
        root,
        spacer,
        title,
        editor,
        embedBar,
        header,
        embedBarContainer,
        bodyErrorMessage,
        containerWidth,
        embedBarBottomFull,
        modernFrame,
        embedBarTop,
        embedBarBottom,
        titleErrorMessage,
        categoryErrorParagraph,
        titleErrorParagraph,
    };
});
