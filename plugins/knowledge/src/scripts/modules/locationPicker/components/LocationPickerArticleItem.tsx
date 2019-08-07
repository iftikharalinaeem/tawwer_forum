/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { folderContentsClasses } from "@knowledge/modules/locationPicker/folderContentsStyles";
import classNames from "classnames";
import React from "react";
import { article } from "@knowledge/navigation/navigationManagerIcons";
import { CheckCompactIcon } from "@library/icons/common";

interface IProps {
    name: string;
    isSelected: boolean;
}

/**
 * Component representing a single navigation item in a list of navigation items.
 */
export default class LocationPickerArticleItem extends React.Component<IProps> {
    public render() {
        const { name, isSelected } = this.props;
        const classesFolderContents = folderContentsClasses();
        return (
            <li className={classNames("folderContents-item", classesFolderContents.item)}>
                <span className={classNames("folderContents-content", classesFolderContents.content)}>
                    <span
                        className={classNames(
                            "folderContents-icon",
                            "folderContents-articleIcon",
                            classesFolderContents.icon,
                        )}
                    >
                        {isSelected ? <CheckCompactIcon className={classesFolderContents.checkIcon} /> : article()}
                    </span>
                    <span className={classNames("folderContents-label", classesFolderContents.label)}>{name}</span>
                </span>
            </li>
        );
    }
}
