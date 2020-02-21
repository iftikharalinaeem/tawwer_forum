/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { themeColorPickerClasses } from "./themeColorPickerStyles";
import { ThemeEditorRoute } from "@themingapi/routes/themeEditorRoutes";

interface IProps {}

export default function ThemeColorPicker(props: IProps) {
    const classes = themeColorPickerClasses();
    return (
        <>
            <div className={classes.wrapper}>
                <div className={classes.frame}>
                    <iframe src={ThemeEditorRoute.url({ preview: true })} width="100%" height="100%"></iframe>
                </div>
                <div className={classes.styleOptions}></div>
            </div>
        </>
    );
}
