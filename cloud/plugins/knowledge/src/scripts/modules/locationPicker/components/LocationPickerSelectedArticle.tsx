/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { folderContentsClasses } from "@knowledge/modules/locationPicker/folderContentsStyles";
import { locationPickerClasses } from "@knowledge/modules/locationPicker/locationPickerStyles";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import React from "react";
import { CheckCompactIcon } from "@library/icons/common";

interface IProps {
    className?: string;
    title: string;
}

/**
 * Component representing a single navigation item in a list of navigation items.
 */
export default class LocationPickerSelectedArticle extends React.Component<IProps> {
    public render() {
        const { className, title } = this.props;
        const classes = locationPickerClasses();
        const classesFolderContents = folderContentsClasses();

        return (
            <li className={classNames("folderContents-item", classesFolderContents.item)}>
                <Button
                    onClick={() => {}}
                    baseClass={ButtonTypes.CUSTOM}
                    className={classNames(classes.articlePlaceholder, "isActive", className)}
                >
                    <CheckCompactIcon className={classes.checkMark} />
                    {title}
                </Button>
            </li>
        );
    }
}
