/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import {
    paddings,
    placeholderStyles,
    textInputSizing,
    colorOut,
    unit,
    absolutePosition,
    pointerEvents,
    margins,
    negative,
    sticky,
} from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { formElementsVariables } from "@library/forms/formElementStyles";
import { calc, percent, px, viewHeight } from "csx";
import { vanillaHeaderVariables } from "@library/headers/vanillaHeaderStyles";
import { richEditorVariables } from "@rich-editor/editor/richEditorVariables";
import { layoutVariables } from "@library/layout/layoutStyles";

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
        overflow: "hidden",
        paddingBottom: px(12),
    });

    const spacer = style("spacer", {
        marginBottom: layoutVars.gutter.size,
    });

    const textWrap = style("textWrap", {
        ...paddings({
            top: 0,
            bottom: 0,
            right: unit(globalVars.gutter.quarter),
            left: unit(globalVars.gutter.quarter),
        }),
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

    const containerWidth = style(
        "containerWidth",
        {
            width: percent(100),
            maxWidth: layoutVars.middleColumnWidth,
        },
        paddings({ horizontal: 12 }),
        margins({
            horizontal: "auto",
        }),
    );

    const editor = style("editor", {
        borderTopLeftRadius: 0,
        borderTopRightRadius: 0,
        marginTop: unit(-formElementVars.border.width),
        display: "flex",
        flexGrow: 1,
        flexDirection: "column",
        $nest: {
            "& .richEditor-text": {},
        },
    });

    const body = style("body", {
        overflow: "auto",
    });

    const embedBarContainer = style("embedBarContainer", sticky(), {
        borderBottom: `${unit(formElementVars.border.width)} solid ${colorOut(formElementVars.border.color)}`,
        top: 0,
        zIndex: 2,
        background: colorOut(vars.colors.bg),
    });

    const embedBar = style("embedBar", {});

    const formContent = style("formContent", {
        display: "flex",
        flexDirection: "column",
        flexGrow: 1,
    });

    const modernFrame = style("modernFrame", {
        position: "relative",
        ...paddings({
            top: globalVars.gutter.half,
            bottom: globalVars.gutter.size,
        }),
    });

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
        textWrap,
        title,
        editor,
        modernFrame,
        body,
        embedBar,
        embedBarContainer,
        formContent,
        bodyErrorMessage,
        containerWidth,
        titleErrorMessage,
        categoryErrorParagraph,
        titleErrorParagraph,
    };
});
