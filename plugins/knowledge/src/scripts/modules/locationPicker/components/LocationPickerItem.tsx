/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import classNames from "classnames";
import { rightChevron, categoryIcon, checkCompact } from "@library/components/icons/common";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import { t } from "@library/application";
import { INavigationTreeItem } from "@library/@types/api";

interface IProps {
    isInitialSelection: boolean;
    isSelected: boolean;
    name: string;
    value: INavigationTreeItem;
    onNavigate: () => void;
    onSelect: (event: React.SyntheticEvent) => void;
}

/**
 * Component representing a single navigation item in a list of navigation items.
 */
export default class LocationPickerItem extends React.Component<IProps> {
    public render() {
        const { value, name, isSelected, isInitialSelection, onSelect } = this.props;
        const isCategory = value.recordType === "knowledgeCategory";
        return (
            <li className="folderContents-item">
                <label className="folderContents-folder">
                    <input
                        type="radio"
                        className={classNames("folderContents-input", "sr-only")}
                        name={name}
                        value={value.recordID}
                        checked={isSelected}
                        onChange={onSelect}
                    />
                    <span className="folderContents-content">
                        <span
                            className={classNames("folderContents-icon", {
                                "folderContents-checkIcon": isSelected,
                                "folderContents-folderIcon": !isSelected,
                                isInitialSelection,
                            })}
                            aria-hidden={true}
                        >
                            {isSelected ? checkCompact() : categoryIcon()}
                        </span>
                        <span className="folderContents-label">{value.name}</span>
                    </span>
                </label>
                {isCategory && (
                    <Button
                        onClick={this.props.onNavigate}
                        baseClass={ButtonBaseClass.ICON}
                        className="folderContents-subFolder"
                    >
                        {rightChevron()}
                        <span className="sr-only">{t("Sub folder")}</span>
                    </Button>
                )}
            </li>
        );
    }
}
