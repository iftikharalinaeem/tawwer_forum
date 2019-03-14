/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@library/dom/appUtils";
import classNames from "classnames";
import React from "react";
import { locationPickerClasses } from "@knowledge/../../../../../../../../knowledge/plugins/knowledge/src/scripts/modules/locationPicker/locationPickerStyles";
import { folderContentsClasses } from "@knowledge/modules/locationPicker/folderContentsStyles";

interface IProps {}

/**
 * Component representing a single navigation item in a list of navigation items.
 */
export default class LocationPickerEmpty extends React.Component<IProps> {
    public render() {
        const classes = locationPickerClasses();
        const classesFolderContents = folderContentsClasses();
        return (
            <li className={classNames("folderContents-item", classesFolderContents.item)}>
                <span className={classes.instructions}>{t("There are no categories here.")}</span>
            </li>
        );
    }
}
