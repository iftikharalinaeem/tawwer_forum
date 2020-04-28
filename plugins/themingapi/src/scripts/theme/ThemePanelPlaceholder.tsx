/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { LoadingRectange, LoadingSpacer } from "@vanilla/library/src/scripts/loaders/LoadingRectangle";
import { themeRevisionPageClasses } from "@themingapi/theme/themeRevisionsPageStyles";

export function ThemePanelPlaceholder() {
    const classes = themeRevisionPageClasses();
    return (
        <div className={classes.padding}>
            <LoadingRectange height={12} width={"75%"} />
            <LoadingSpacer height={18} />
            <LoadingRectange height={12} width={"70%"} />
            <LoadingSpacer height={18} />
            <LoadingRectange height={12} width={"80%"} />
            <LoadingSpacer height={18} />
            <LoadingRectange height={12} width={"80%"} />
            <LoadingSpacer height={18} />
            <LoadingRectange height={12} width={"80%"} />
        </div>
    );
}
