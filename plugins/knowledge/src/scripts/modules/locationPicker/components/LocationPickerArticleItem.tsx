/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IKbNavigationItem, KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import { t } from "@library/application";
import { ButtonBaseClass } from "@library/components/forms/Button";
import { article } from "@library/components/icons";
import { categoryIcon, checkCompact, rightChevron } from "@library/components/icons/common";
import classNames from "classnames";
import React from "react";
import { knowldedgeBaseItem } from "@knowledge/icons/common";
import { folderContentsClasses } from "@library/styles/folderContentsStyles";

interface IProps {
    name: string;
}

/**
 * Component representing a single navigation item in a list of navigation items.
 */
export default class LocationPickerArticleItem extends React.Component<IProps> {
    public render() {
        const { name } = this.props;
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
                        {article()}
                    </span>
                    <span className={classNames("folderContents-label", classesFolderContents.label)}>{name}</span>
                </span>
            </li>
        );
    }
}
