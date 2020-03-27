import { useThemeCache, styleFactory, variableFactory } from "@library/styles/styleUtils";
import { unit, paddings, margins } from "@library/styles/styleHelpers";
import { globalVariables } from "@vanilla/library/src/scripts/styles/globalStyleVars";

export const webhookAddEditClasses = useThemeCache(() => {
    const makeVars = variableFactory("webhookAddEdit");
    const globalVars = globalVariables();
    const style = styleFactory("webhookAddEdit");

    const inlinePullRight = style("inlinePullRight", {
        display: "flex",
        flexWrap: "wrap",
        alignItems: "flex-end",
        ...paddings({
            vertical: globalVars.gutter.half,
            horizontal: globalVars.gutter.size * 2
        }),
    });

    const inlineCheckbox = style("inlineCheckbox", {
        fontWeight: globalVars.fonts.weights.normal,
        flexBasis: "50%"
    });

    const statusRadio = style("statusRadio", {
        fontWeight: globalVars.fonts.weights.normal,
    });

    return { inlinePullRight, inlineCheckbox, statusRadio };
});