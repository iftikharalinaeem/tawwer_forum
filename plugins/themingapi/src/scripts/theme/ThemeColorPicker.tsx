/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { themeColorPickerClasses } from "./themeColorPickerStyles";

interface IProps {}

export default function ThemeColorPicker(props: IProps) {
    const classes = themeColorPickerClasses();
    return (
        <>
            <div className={classes.wrapper}>
                <div className={classes.frame}></div>
                <div className={classes.styleOptions}></div>
            </div>
        </>
    );
}
