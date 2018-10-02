/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import classNames from "classnames";
import { IKbNavigationItem } from "@knowledge/@types/api";
import { rightChevron, check } from "@library/components/Icons";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import { t } from "@library/application";

interface IProps {
    isInitialSelection: boolean;
    isSelected: boolean;
    name: string;
    value: IKbNavigationItem;
    onNavigate: () => void;
    onSelect: (event: React.SyntheticEvent) => void;
}

export default class NavigationItem extends React.Component<IProps> {
    public render() {
        const { value, name, isSelected, isInitialSelection, onSelect } = this.props;

        return (
            <li className="folderContents-item">
                <label className="folderContents-folder">
                    <input
                        type="radio"
                        className={classNames("folderContents-input", {
                            initialSelection: isInitialSelection,
                        })}
                        name={name}
                        value={value.recordID}
                        checked={isSelected}
                        onChange={onSelect}
                    />
                    <span className="dropDownRadio-check" aria-hidden={true}>
                        {isSelected && check()}
                    </span>
                    <span className="dropDownRadio-label">{value.name}</span>
                </label>
                {value.recordType === "knowledgeCategory" &&
                    value.children &&
                    value.children.length > 0 && (
                        <Button onClick={this.props.onNavigate} baseClass={ButtonBaseClass.ICON}>
                            {rightChevron()}
                            <span className="sr-only">{t("Sub folder")}</span>
                        </Button>
                    )}
            </li>
        );
    }
}
