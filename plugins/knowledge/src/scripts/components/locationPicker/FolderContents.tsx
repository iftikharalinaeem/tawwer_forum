/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { t } from "@library/application";
import { getRequiredID } from "@library/componentIDs";
import Button from "@dashboard/components/forms/Button";
import { rightChevron } from "@library/components/Icons";
import { check } from "@library/components/Icons";

interface ICategory {
    name: string;
    displayType?: string;
    isSection: boolean;
    url: string;
    parentID: number;
    recordID: number;
    recordType: string;
    children?: ICategory[];
}

interface IProps {
    currentFolderData: any;
    children: ICategory[];
}

interface IState {
    id: string;
    selectedRecordID?: number;
}

/**
 * This component allows to display and edit the location of the current page.
 * Calls the LocationChooser component when clicked.
 */
export default class FolderContents extends React.Component<IProps, IState> {
    public constructor(props) {
        super(props);
        this.state = {
            id: getRequiredID(props, "locationPicker"),
            selectedRecordID: this.props.currentFolderData.recordID,
        };
    }

    public tempClick = () => {
        alert("do click");
    };

    public get radioName(): string {
        return "folders-" + this.state.id;
    }

    public render() {
        const contents = this.props.children.map(item => {
            const isSelected = !!this.state.selectedRecordID && this.state.selectedRecordID === item.recordID;
            return (
                <li className="folderContents-item">
                    <label className="folderContents-folder">
                        <input
                            type="radio"
                            className={classNames("folderContents-input", {
                                initialSelection: this.props.currentFolderData.recordID === this.state.selectedRecordID,
                            })}
                            name={this.radioName}
                            value={item.recordID}
                            checked={isSelected}
                            onChange={this.onChange}
                        />
                        <span className="dropDownRadio-check" aria-hidden={true}>
                            {isSelected && check()}
                        </span>
                        <span className="dropDownRadio-label">{item.name}</span>
                    </label>
                    {item.children &&
                        item.children.length > 0 && (
                            <Button onClick={this.tempClick}>
                                {rightChevron()}
                                <span className="sr-only">{t("Sub folder")}</span>
                            </Button>
                        )}
                </li>
            );
        });

        return (
            <fieldset className={classNames("folderContents")}>
                <legend className="sr-only">{t("Contents of folder: " + this.props.currentFolderData.name)}</legend>
                <ul className="folderContents-items">{contents}</ul>
            </fieldset>
        );
    }

    private onChange = e => {
        this.setState({
            selectedRecordID: e.currentTarget.value,
        });
    };
}
