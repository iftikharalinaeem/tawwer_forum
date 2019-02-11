/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
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

interface IProps {
    name: string;
}

/**
 * Component representing a single navigation item in a list of navigation items.
 */
export default class LocationPickerArticleItem extends React.Component<IProps> {
    public render() {
        const { name } = this.props;
        return (
            <li className={classNames("folderContents-item")}>
                <span className="folderContents-content">
                    <span className={classNames("folderContents-icon", "folderContents-articleIcon")}>{article()}</span>
                    <span className="folderContents-label">{name}</span>
                </span>
            </li>
        );
    }
}
