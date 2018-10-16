/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { t } from "@library/application";
import Container from "@knowledge/layouts/components/Container";
import PanelLayout from "@knowledge/layouts/PanelLayout";
import { PanelWidget } from "@knowledge/layouts/PanelLayout";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import { Devices } from "@library/components/DeviceChecker";
import BackLink from "@library/components/BackLink";
import Button from "@library/components/forms/Button";
import classNames from "classnames";
import SelectBox from "@library/components/SelectBox";
import { dummyOtherLanguagesData } from "../../categories/state/dummyOtherLanguages";
import { getRequiredID } from "@library/componentIDs";
import EditorMenu from "./EditorMenu";

interface IProps {
    device: Devices;
    backUrl: string | null;
    canSubmit: boolean;
    selectedKey?: string;
}

/**
 * Implement editor header component
 */
export default class EditorHeader extends React.Component<IProps> {
    private localeTitleID;

    public constructor(props) {
        super(props);
        this.localeTitleID = getRequiredID(props, "editorHeader");
    }

    public render() {
        let foundIndex = false;
        const processedChildren = dummyOtherLanguagesData.map(language => {
            // This is all hard coded for now.
            const selected = language.key === this.props.selectedKey;
            language.selected = selected;
            if (selected) {
                foundIndex = selected;
            }
            return language;
        });
        if (!foundIndex) {
            processedChildren[0].selected = true;
        }

        const label = t("Switch Locale");

        return (
            <div className="editorHeader">
                <ul className="editorHeader-items">
                    {this.props.backUrl && (
                        <li className="editorHeader-item">
                            <BackLink url={this.props.backUrl} visibleLabel={true} className="editorHeader-backLink" />
                        </li>
                    )}

                    <li className="editorHeader-item">
                        <Button
                            title={label}
                            disabled={!this.props.canSubmit}
                            className={classNames("editorHeader-publish", "button-primary")}
                        >
                            {t("Publish")}
                        </Button>
                    </li>
                    <li className="editorHeader-item">
                        <h3 id={this.localeTitleID} className="sr-only">
                            {label}
                        </h3>
                        <SelectBox
                            describedBy={this.localeTitleID}
                            className="editorHeader-otherLanguages"
                            stickRight={true}
                        >
                            {processedChildren}
                        </SelectBox>
                    </li>
                    <li className="editorHeader-item">
                        <EditorMenu buttonClassName="editorHeader-menu" />
                    </li>
                </ul>
            </div>
        );
    }
}
