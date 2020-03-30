/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IKbNavigationItem, KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import { t } from "@library/utility/appUtils";
import Button from "@library/forms/Button";
import classNames from "classnames";
import React from "react";
import { knowledgeBaseItem } from "@knowledge/icons/common";
import { folderContentsClasses } from "@knowledge/modules/locationPicker/folderContentsStyles";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { CategoryIcon, CheckCompactIcon, RightChevronIcon } from "@library/icons/common";

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
                                isSelected ? classesFolderContents.checkIcon : classesFolderContents.uncheckedIcon,
                                {
                                    "folderContents-checkIcon": isSelected,
                                    "folderContents-folderIcon": !isSelected,
                                    isInitialSelection,
                                },
                            )}
                            aria-hidden={true}
                        >
                            {isSelected ? <CheckCompactIcon /> : this.typeIcon}
                        </span>
                        <span className={classNames("folderContents-label", classesFolderContents.label)}>
                            {value.name}
                        </span>
                    </span>
                </label>
                {isNavigable && (
                    <Button
                        onClick={this.props.onNavigate}
                        baseClass={ButtonTypes.CUSTOM}
                        className={classNames("folderContents-subFolder", classesFolderContents.subFolder)}
                    >
                        <RightChevronIcon className={classNames("folderContents-chevron", "isSmall")} centred={true} />
                        <span className="sr-only">{t("Sub folder")}</span>
                    </Button>
                )}
            </li>
        );
    }

    private get typeIcon(): React.ReactNode {
        switch (this.props.value.recordType) {
            case KbRecordType.CATEGORY:
                return <CategoryIcon />;
            case KbRecordType.KB:
                return knowledgeBaseItem();
        }
    }
}
