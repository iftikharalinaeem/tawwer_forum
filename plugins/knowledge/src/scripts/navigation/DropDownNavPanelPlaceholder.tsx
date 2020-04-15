/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { LoadingRectange, LoadingSpacer } from "@vanilla/library/src/scripts/loaders/LoadingRectangle";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import classNames from "classnames";

export function DropDownNavPanelPlaceholder() {
    const classes = dropDownClasses();
    return (
        <div className={classes.panelContent}>
            <LoadingRectange height={0.5} width={"100%"} />
            <div className={classNames(classes.paddedFrame, classes.items)}>
                <LoadingSpacer height={18} />
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
        </div>
    );
}
