/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect, useRef, useState } from "react";
import Container from "@library/layout/components/Container";
import { themeColorPickerClasses } from "./themeColorPickerStyles";

interface IProps {}

export default function ThemeColorPicker(props: IProps) {
    const classes = themeColorPickerClasses();
    return (
        <>
            <div>
                <div className={classes.wrapper}>
                    <div className={classes.frame}></div>
                    <div className={classes.options}></div>
                </div>
            </div>
        </>
    );
}
