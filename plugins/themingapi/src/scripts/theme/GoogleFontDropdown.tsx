/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { ThemeDropDown } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeDropDown";
import { useThemeBuilder } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderContext";

interface IProps {
    setCustomFont: (value: boolean) => void;
    setInitialValue?: boolean;
}

export function GoogleFontDropdown(props: IProps) {
    const { setVariableValue, rawThemeVariables } = useThemeBuilder();
    const { setInitialValue, setCustomFont } = props;

    const variableKey = "global.fonts.googleFontFamily";
    return (
        <ThemeDropDown
            // This is actually an array, but the first is the real one. The rest are fallbacks.
            variableKey={variableKey}
            triggerOnChangeOnInit={true}
            afterChange={value => {
                setVariableValue("global.fonts.forceGoogleFont", !!value);
                console.log("after change value: ", value);
                console.log('after value == "custom": ', value == "custom");
                props.setCustomFont && props.setCustomFont(value == "custom");
            }}
            options={[
                { label: "Open Sans", value: "Open Sans" },
                { label: "Roboto", value: "Roboto" },
                { label: "Lato", value: "Lato" },
                { label: "Montserrat", value: "Montserrat" },
                { label: "Roboto Condensed", value: "Roboto Condensed" },
                { label: "Source Sans Pro", value: "Source Sans Pro" },
                { label: "Merriweather", value: "Merriweather" },
                { label: "Raleway", value: "Raleway" },
                { label: "Roboto Mono", value: "Roboto Mono" },
                { label: "Poppins", value: "Poppins" },
                { label: "Nunito", value: "Nunito" },
                { label: "PT Serif", value: "PT Serif" },
                { label: "Custom Font", value: "custom" },
            ]}
        ></ThemeDropDown>
    );
}
