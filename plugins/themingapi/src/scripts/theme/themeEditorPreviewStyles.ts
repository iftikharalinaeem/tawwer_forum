/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { unit, paddings } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { globalVariables } from "@vanilla/library/src/scripts/styles/globalStyleVars";
import { FontSizeProperty, HeightProperty, MarginProperty, PaddingProperty, WidthProperty } from "csstype";
import { TLength } from "typestyle/lib/types";
import { TileAlignment } from "@vanilla/library/src/scripts/features/tiles/Tiles";
import { titleBarClasses } from "@vanilla/library/src/scripts/headers/titleBarStyles";
import titleBarNavClasses from "@vanilla/library/src/scripts/headers/titleBarNavStyles";
import { searchBarVariables, searchBarClasses } from "@vanilla/library/src/scripts/features/search/searchBarStyles";
import { bannerClasses } from "@vanilla/library/src/scripts/banner/bannerStyles";
import { dropDownClasses } from "@vanilla/library/src/scripts/flyouts/dropDownStyles";

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
        }
    });
    const buttonStyles = previewVars("buttonStyles", {
        padding: {
            top: 40,
        }
    });
    const inputStyles = previewVars("buttonStyles", {
        padding: {
            top: 30,
            bottom: 30
        }
    });

    const title = previewVars("title", {
        margin: {
            bottom: 18
        }
    })
    return {
        content,
        description,
        styleContent,
        buttonStyles,
        inputStyles,
        title
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
    });
    const description = style("description", {
        fontSize: unit(vars.description.fontSize),
        lineHeight: vars.description.lineHeight,
    });

    const title = style("title", {
        marginBottom: vars.title.margin.bottom
    });

    const buttonStyles = style("buttonStyles", {
        ...paddings({
            top: vars.buttonStyles.padding.top,
        })
    });
    const inputStyles = style("inputStyles", {
        ...paddings({
            top: vars.inputStyles.padding.top,
            bottom: vars.inputStyles.padding.bottom,
        })
    });
    const styleContent = style("styleContent", {
        paddingTop: unit(vars.styleContent.padding.top),
        display: "flex",

        $nest: {
            "&& button": {
                marginRight: unit(12),
            }
        }

    });

    const contentContainer = style("contentContainer", {
        $nest: {
            [`&& #page`]: {
                overflow: "auto"
            },
            [`& .${titleBarClasses().logoContainer}`]: {
                pointerEvents: "none"
            },
            [`& .${dropDownClasses().action}`]: {
                pointerEvents: "none"
            },
            [`& .${titleBarNavClasses().link}`]: {
                pointerEvents: "none"
            }
            ,
            [`& .${bannerClasses().searchButton}`]: {
                pointerEvents: "none"
            }
        }
    });

    return {
        content,
        description,
        buttonStyles,
        inputStyles,
        title,
        styleContent,
        contentContainer,
    };
});

export default themeEditorPreviewClasses;
