/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { LoadingCircle, LoadingRectange, LoadingSpacer } from "@vanilla/library/src/scripts/loaders/LoadingRectangle";
import { themeRevisionPageClasses } from "@themingapi/theme/themeRevisionsPageStyles";
import { themeEditorClasses } from "@themingapi/theme/ThemeEditor.styles";
import { themeBuilderClasses } from "@library/forms/themeEditor/ThemeBuilder.styles";
import classNames from "classnames";

export function ThemePanelPlaceholder() {
    const revisionPageClasses = themeRevisionPageClasses();

    const classes = themeBuilderClasses();

    return (
        <div className={classes.root}>
            <div className={classes.section}>
                <LoadingSpacer height={5} />
                <LoadingRectange style={{ margin: "auto" }} height={18} width={"50%"} />
                <LoadingSpacer height={20} />
                <div className={revisionPageClasses.itemLabelContainer}>
                    <div className={revisionPageClasses.revisionItem}>
                        <LoadingCircle height={18} />
                        <span className={classNames(revisionPageClasses.itemLabel)}>
                            <LoadingRectange height={12} width={"65%"} />
                            <LoadingSpacer height={5} />
                            <LoadingRectange height={12} width={"35%"} />
                        </span>
                    </div>
                </div>
                <div className={revisionPageClasses.itemLabelContainer}>
                    <div className={revisionPageClasses.revisionItem}>
                        <LoadingCircle height={18} />
                        <span className={classNames(revisionPageClasses.itemLabel)}>
                            <LoadingRectange height={12} width={"65%"} />
                            <LoadingSpacer height={5} />
                            <LoadingRectange height={12} width={"35%"} />
                        </span>
                    </div>
                </div>
                <div className={revisionPageClasses.itemLabelContainer}>
                    <div className={revisionPageClasses.revisionItem}>
                        <LoadingCircle height={18} />
                        <span className={classNames(revisionPageClasses.itemLabel)}>
                            <LoadingRectange height={12} width={"65%"} />
                            <LoadingSpacer height={5} />
                            <LoadingRectange height={12} width={"35%"} />
                        </span>
                    </div>
                </div>
                <div className={revisionPageClasses.itemLabelContainer}>
                    <div className={revisionPageClasses.revisionItem}>
                        <LoadingCircle height={18} />
                        <span className={classNames(revisionPageClasses.itemLabel)}>
                            <LoadingRectange height={12} width={"50%"} />
                            <LoadingSpacer height={5} />
                            <LoadingRectange height={12} width={"35%"} />
                        </span>
                    </div>
                </div>
            </div>
        </div>
    );
}
