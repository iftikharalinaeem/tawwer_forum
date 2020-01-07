/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut, unit } from "@library/styles/styleHelpers";
import { useThemeCache, styleFactory, variableFactory } from "@library/styles/styleUtils";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { calc, color, percent, px } from "csx";
import { panelWidgetVariables } from "@library/layout/panelWidgetStyles";

export const panelBackgroundVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("panelBackgroundStyles");
    const vars = globalVariables();
    const layoutVars = layoutVariables();

    const config = makeThemeVars("config", {
        render: true,
    });

    const colors = makeThemeVars("colors", {
        backgroundColor: color("#dee3eb"),
    });

    return { config, colors };
});

export const panelBackgroundClasses = useThemeCache(() => {
    const style = styleFactory("panelBackground");

    const vars = panelBackgroundVariables();
    const layoutVars = layoutVariables();
    const widgetVars = panelWidgetVariables();

    const root = style({
        position: "absolute",
        left: 0,
        height: percent(100),
        width: calc(`50% - ${unit(layoutVars.middleColumn.paddedWidth / 2)}`),
        minWidth: unit(layoutVars.panel.paddedWidth + widgetVars.spacing.padding * 2),
        backgroundColor: colorOut(vars.colors.backgroundColor),
        zIndex: 0,
    });

    return {
        root,
    };
});
