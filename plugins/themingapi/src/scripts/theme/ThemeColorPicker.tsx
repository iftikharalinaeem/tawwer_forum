/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { themeColorPickerClasses } from "./themeColorPickerStyles";
import { ThemeEditorRoute } from "@themingapi/routes/themeEditorRoutes";

export interface IProps {
    themeID: number | string;
}

export default function ThemeColorPicker(props: IProps) {
    const classes = themeColorPickerClasses();
    return (
        <>
            <div className={classes.wrapper}>
                <div className={classes.frame}>
                    <iframe
                        src="https://dev.vanilla.localhost/theme/theme-settings/preview"
                        width="100%"
                        height="100%"
                    ></iframe>
                </div>
                <div className={classes.styleOptions}></div>
            </div>
        </>
    );
}
