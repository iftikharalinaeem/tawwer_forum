/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { isAllowedUrl } from "@library/utility/appUtils";
import { t } from "@vanilla/i18n/src";
import { ThemeInputText } from "@library/forms/themeEditor/ThemeInputText";

interface IProps {}

function urlValidation(url: any) {
    return url ? isAllowedUrl(url.toString()) : false;
}

export function CustomFontUrl(props: IProps) {
    return (
        <ThemeInputText
            varKey={"global.fonts.customFont.url"}
            debounceTime={10}
            validation={newValue => {
                return newValue !== "" || urlValidation(newValue);
            }}
            errorMessage={t("Invalid URL")}
        />
    );

    /*
    // const { setVariableValue } = useThemeBuilder();

    const { generatedValue, initialValue, rawValue, defaultValue, setValue, error, setError } = useThemeVariableField(
        customFontUrlKey,
    );

    // const { generatedValue, initialValue } = useThemeVariableField(customFontUrlKey);
    const [valid, setValid] = useState(false);

    useEffect(() => {
        setValid(generatedValue !== "" || urlValidation(generatedValue));
    }, [generatedValue]);

    // initial value
    useEffect(() => {
        setValid(generatedValue !== "" || urlValidation(initialValue));
    }, []);

    // Debounced internal function for onPickerChange.
    // Be sure to always use it through the following ref so that we the function identitity,
    // While still preserving the debounce.
    // This article explains the issue being worked around here https://dmitripavlutin.com/react-hooks-stale-closures/
    const _debounceInput = useCallback(
        debounce(
            (newValue: string) => {
                setValue(newValue);
            },
            16,
            { trailing: true },
        ),
        [],
    );

    return (
        <InputTextBlock
            errors={
                valid
                    ? undefined
                    : [
                          {
                              message: t("Invalid URL"),
                          },
                      ]
            }
            inputProps={{
                defaultValue: initialValue,
                value: generatedValue,
                onChange: event => {
                    _debounceInput(event.target.value);
                },
            }}
        />
    );
    */
}
