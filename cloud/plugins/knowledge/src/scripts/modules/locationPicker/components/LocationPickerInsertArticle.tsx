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

interface IProps {
    onClick: (position: string) => void;
    isSelected: boolean;
    className?: string;
}

/**
 * Component representing a single navigation item in a list of navigation items.
 */
export default class LocationPickerInsertArticle extends React.Component<IProps> {
    public render() {
        const { onClick, isSelected, className } = this.props;
        const title = t("Insert Article Here");
        const classes = locationPickerClasses();
        const classesFolderContents = folderContentsClasses();
        return (
            <li className={classNames("folderContents-item", classesFolderContents.item)}>
                <Button
                    title={title}
                    onClick={onClick}
                    baseClass={ButtonTypes.CUSTOM}
                    className={classNames(classes.articlePlaceholder, { isActive: isSelected }, className)}
                >
                    <span className="sr-only">{title}</span>
                </Button>
            </li>
        );
    }
}
