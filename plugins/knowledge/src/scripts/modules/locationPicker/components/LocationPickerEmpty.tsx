/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@library/application";
import classNames from "classnames";
import React from "react";
import locationPickerStyles from "@knowledge/styles/locationPickerStyles";

interface IProps {}

/**
 * Component representing a single navigation item in a list of navigation items.
 */
export default class LocationPickerEmpty extends React.Component<IProps> {
    public render() {
        const classes = locationPickerStyles();
        return (
            <li className={classNames("folderContents-item")}>
                <span className={classes.instructions}>{t("There are no categories here.")}</span>
            </li>
        );
    }
}
