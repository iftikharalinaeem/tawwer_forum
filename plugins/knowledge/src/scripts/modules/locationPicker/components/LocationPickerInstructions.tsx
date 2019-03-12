/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@library/application";
import classNames from "classnames";
import React from "react";
import { locationPickerClasses } from "@knowledge/styles/locationPickerStyles";
import { folderContentsClasses } from "@library/styles/folderContentsStyles";

interface IProps {}

/**
 * Component representing a single navigation item in a list of navigation items.
 */
export default class LocationPickerInstructions extends React.Component<IProps> {
    public render() {
        const classes = locationPickerClasses();
        const classesFolderContents = folderContentsClasses();
        return (
            <li className={classNames("folderContents-item", classesFolderContents.item)}>
                <span className={classes.instructions}>{t("Select where you want to place your Article.")}</span>
            </li>
        );
    }
}
