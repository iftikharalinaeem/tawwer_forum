/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState } from "react";
import { themeBuilderClasses } from "@library/forms/themeEditor/ThemeBuilder.styles";
import { ThemeBuilderSectionGroup } from "@library/forms/themeEditor/ThemeBuilderSectionGroup";

export function ThemeRevisionsPanel() {
    const classes = themeBuilderClasses();
    return (
        <>
            <div className={classes.root}>
                <ThemeBuilderSectionGroup label={"Revisions"}></ThemeBuilderSectionGroup>
            </div>
        </>
    );
}
