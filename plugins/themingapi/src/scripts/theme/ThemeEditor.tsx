/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
<<<<<<< HEAD:plugins/themingapi/src/scripts/theme/ThemeColorPicker.tsx
import { themeColorPickerClasses } from "./themeColorPickerStyles";
import { ThemeEditorRoute } from "@themingapi/routes/themeEditorRoutes";
=======
import { themeEditorClasses } from "./themeEditorStyles";
>>>>>>> 8635eb7935db360c57c37972b1cb9b79af58482d:plugins/themingapi/src/scripts/theme/ThemeEditor.tsx

export interface IProps {
    themeID: number | string;
}

export default function ThemeEditor(props: IProps) {
    const classes = themeEditorClasses();
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
