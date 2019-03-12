/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IKbNavigationItem, KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import { t } from "@library/application";
import Button from "@library/components/forms/Button";
import { article } from "@library/components/icons";
import { categoryIcon, checkCompact, rightChevron } from "@library/components/icons/common";
import classNames from "classnames";
import React from "react";
import { knowldedgeBaseItem } from "@knowledge/icons/common";
import { folderContentsClasses } from "@library/styles/folderContentsStyles";
import { ButtonTypes } from "@library/styles/buttonVariables";

interface IProps {
    isInitialSelection: boolean;
    isSelected: boolean;
    name: string;
    value: IKbNavigationItem;
    onNavigate: () => void;
    onSelect: (event: React.SyntheticEvent) => void;
    selectable?: boolean;
}

/**
 * Component representing a single navigation item in a list of navigation items.
 */
export default class LocationPickerCategoryItem extends React.Component<IProps> {
    public render() {
        const { value, name, isSelected, isInitialSelection, onSelect, selectable } = this.props;
        const isNavigable = [KbRecordType.CATEGORY, KbRecordType.KB].includes(value.recordType);
        const classesFolderContents = folderContentsClasses();
        return (
            <li className={classNames("folderContents-item", classesFolderContents.item, { isActive: isSelected })}>
                <label className={classNames("folderContents-folder", classesFolderContents.folder)}>
                    <input
                        type="radio"
                        className={classNames("folderContents-input", classesFolderContents.input, "sr-only", {
                            isSelectable: selectable,
                        })}
                        name={name}
                        value={value.recordID}
                        checked={isSelected}
                        onChange={onSelect}
                        disabled={!isNavigable}
                    />
                    <span className={classNames("folderContents-content", classesFolderContents.content)}>
                        <span
                            className={classNames(
                                "folderContents-icon",
                                classesFolderContents.icon,
                                classesFolderContents.checkIcon,
                                {
                                    "folderContents-checkIcon": isSelected,
                                    "folderContents-folderIcon": !isSelected,
                                    isInitialSelection,
                                },
                            )}
                            aria-hidden={true}
                        >
                            {isSelected ? checkCompact() : this.typeIcon}
                        </span>
                        <span className={classNames("folderContents-label", classesFolderContents.label)}>
                            {value.name}
                        </span>
                    </span>
                </label>
                {isNavigable && (
                    <Button
                        onClick={this.props.onNavigate}
                        baseClass={ButtonTypes.ICON}
                        className={classNames("folderContents-subFolder", classesFolderContents.subFolder)}
                    >
                        {rightChevron("folderContents-chevron isSmall", true)}
                        <span className="sr-only">{t("Sub folder")}</span>
                    </Button>
                )}
            </li>
        );
    }

    private get typeIcon(): React.ReactNode {
        switch (this.props.value.recordType) {
            case KbRecordType.CATEGORY:
                return categoryIcon();
            case KbRecordType.KB:
                return knowldedgeBaseItem();
        }
    }
}
