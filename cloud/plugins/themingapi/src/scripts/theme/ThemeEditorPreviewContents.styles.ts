/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import {
    unit,
    paddings,
    fonts,
    colorOut,
    absolutePosition,
    ColorValues,
    flexHelper,
} from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@vanilla/library/src/scripts/styles/globalStyleVars";
import { FontSizeProperty } from "csstype";
import { TLength } from "typestyle/lib/types";
import { userContentVariables } from "@vanilla/library/src/scripts/content/userContentStyles";
import { color, ColorHelper } from "csx";
import { contentBannerVariables } from "@vanilla/library/src/scripts/banner/contentBannerStyles";

export const themeEditorPreviewVariables = useThemeCache(() => {
    const globalVars = globalVariables();
    const previewVars = variableFactory("themeEditorPreview");

    const content = previewVars("content", {
        padding: {
            top: 40,
            left: 0,
            right: 0,
            bottom: 40,
        },
    });

    const description = previewVars("description", {
        fontSize: globalVars.fonts.size.medium as FontSizeProperty<TLength>,
        marginTop: 6,
        lineHeight: globalVars.lineHeights.excerpt,
    });

    const styleContent = previewVars("buttonContent", {
        padding: {
            top: 18,
        },
    });
    const buttonStyles = previewVars("buttonStyles", {
        padding: {
            top: 40,
        },
    });
    const inputStyles = previewVars("buttonStyles", {
        padding: {
            top: 30,
            bottom: 30,
        },
    });

    const title = previewVars("title", {
        margin: {
            bottom: 18,
        },
    });
    return {
        content,
        description,
        styleContent,
        buttonStyles,
        inputStyles,
        title,
    };
});
export const themeEditorPreviewClasses = useThemeCache(() => {
    const vars = themeEditorPreviewVariables();
    const globalVars = globalVariables();
    const style = styleFactory("themeEditorPreview");

    const content = style("content", {
        ...paddings({
            top: vars.content.padding.top,
        }),
        ...fonts(userContentVariables().fonts),
        lineHeight: unit(24),
    });
    const description = style("description", {});

    const title = style("title", {
        marginBottom: vars.title.margin.bottom,
    });

    const buttonStyles = style("buttonStyles", {
        ...paddings({
            top: vars.buttonStyles.padding.top,
        }),
    });
    const inputStyles = style("inputStyles", {
        ...paddings({
            top: vars.inputStyles.padding.top,
            bottom: vars.inputStyles.padding.bottom,
        }),
    });
    const styleContent = style("styleContent", {
        paddingTop: unit(vars.styleContent.padding.top),
        display: "flex",

        $nest: {
            "&& button": {
                marginRight: unit(12),
            },
        },
    });

    const contentContainer = style("contentContainer", {
        $nest: {
            [`&& #page`]: {
                overflow: "auto",
            },
        },
    });

    const panelActivator = style("panelActivator", {
        position: "relative",
    });

    const disabledContentBanner = style("disabledContentBanner", {
        ...flexHelper().middle(),
        height: contentBannerVariables().dimensions.minHeight,
        background: colorOut(globalVars.mixPrimaryAndBg(0.08)),
    });

    const disabledIcon = style("disabledIcon", {
        marginLeft: globalVars.gutter.half,
    });

    const panelActivatorIndicator = (dashColor?: ColorHelper | string) => {
        return style("panelActivatorIndicator", {
            ...absolutePosition.fullSizeOfParent(),
            outline: `1px dashed ${dashColor ? colorOut(dashColor) : color("#fff")}`,
            outlineOffset: unit(-4),
            opacity: 0.85,
            pointerEvents: "none",
            zIndex: 10000,
        });
    };

    return {
        content,
        description,
        buttonStyles,
        inputStyles,
        title,
        styleContent,
        contentContainer,
        panelActivator,
        panelActivatorIndicator,
        disabledContentBanner,
        disabledIcon,
    };
});

export default themeEditorPreviewClasses;