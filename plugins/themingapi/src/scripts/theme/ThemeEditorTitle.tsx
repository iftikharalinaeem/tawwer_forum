/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useRef, useState, useEffect, useCallback } from "react";
import { themeEditorPageClasses } from "@themingapi/theme/themeEditorPageStyles";
import InputTextBlock from "@vanilla/library/src/scripts/forms/InputTextBlock";
import classNames from "classnames";
import Button from "@vanilla/library/src/scripts/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { EditIcon } from "@vanilla/library/src/scripts/icons/common";
import { useThemeActions } from "@themingapi/theme/ThemeEditorActions";
import { visibility } from "@vanilla/library/src/scripts/styles/styleHelpers";
import { style } from "typestyle";
import { t } from "@vanilla/i18n";

interface IProps {
    isDisabled?: boolean;
    setThemeName?: string;
    themeName?: string;
    editThemeName?: void;
    pageType?: string;
}

export const ThemeEditorTitle = (props: IProps) => {
    const { updateAssets } = useThemeActions();
    const inputRef = useRef<HTMLInputElement | null>(null);
    const [name, setName] = useState(props.themeName);
    const classes = themeEditorPageClasses();

    const editThemeName = () => {
        setImmediate(() => {
            inputRef.current?.focus();
        });
    };

    return (
        <li className={classes.themeName}>
            <AutoWidthInput
                required
                onChange={event => {
                    updateAssets({ name: event.target.value, edited: true });
                    setName(event.target.value);
                }}
                className={classNames(classes.themeInput)}
                ref={inputRef}
                value={name}
                placeholder={t("Untitled")}
            />

            <Button
                baseClass={ButtonTypes.ICON_COMPACT}
                onClick={() => {
                    editThemeName();
                }}
            >
                <EditIcon className={classes.editIcon} small={true} />
            </Button>
        </li>
    );
};

const AutoWidthInput = React.forwardRef(function AutoWidthInput(
    props: React.InputHTMLAttributes<HTMLInputElement>,
    ref: React.RefObject<HTMLInputElement>,
) {
    const spanRef = useRef<HTMLSpanElement>(null);
    const [minWidth, setMinWidth] = useState(0);

    const minWidthClass = style({
        $debugName: "autoWidthSizer",
        width: 0,
        minWidth: minWidth,
        color: "#555a62",
    });

    const { value, placeholder } = props;

    const measureSpan = useCallback(
        (content: string | undefined) => {
            if (!spanRef.current) {
                return;
            }

            content = content || placeholder || "";
            spanRef.current.innerText = content;
            // Measure the span widht.
            const rect = spanRef.current.getBoundingClientRect();
            let newWidth = rect.width + 12;
            newWidth = Math.min(Math.max(80, newWidth), 300);
            setMinWidth(newWidth);
        },
        [placeholder],
    );

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const newValue = e.currentTarget.value;
        measureSpan(newValue);
        props.onChange?.(e);
    };

    useEffect(() => {
        measureSpan(value?.toString());
    }, [measureSpan, value]);

    const classes = themeEditorPageClasses();

    return (
        <>
            <input
                {...props}
                ref={ref}
                className={classNames(props.className, minWidthClass)}
                onChange={handleChange}
            />
            <span ref={spanRef} className={classNames(classes.hiddenInputMeasure, props.className)}>
                {props.value}
            </span>
        </>
    );
});
