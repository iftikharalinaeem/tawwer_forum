/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { t } from "@library/application";
import { PanelArea } from "@knowledge/layouts/PanelLayout";
import { PanelWidget } from "@knowledge/layouts/PanelLayout";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import { Devices } from "@library/components/DeviceChecker";
import BackLink from "@library/components/BackLink";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import classNames from "classnames";
import SelectBox from "@library/components/SelectBox";
import { dummyOtherLanguagesData } from "../../categories/state/dummyOtherLanguages";
import { getRequiredID } from "@library/componentIDs";
import EditorMenu from "./EditorMenu";
import ButtonLoader from "@library/components/ButtonLoader";

interface IProps {
    device: Devices;
    backUrl: string | null;
    canSubmit: boolean;
    isSubmitLoading: boolean;
    selectedKey?: string;
    className?: string;
    callToAction?: string;
}

/**
 * Implement editor header component
 */
export default class EditorHeader extends React.Component<IProps> {
    public static defaultProps = {
        callToAction: t("Publish"),
    };

    private localeTitleID;

    public constructor(props) {
        super(props);
        this.localeTitleID = getRequiredID(props, "editorHeader");
    }

    public render() {
        let foundIndex = false;
        const processedChildren = dummyOtherLanguagesData.children.map(language => {
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
            <div className={classNames("editorHeader", this.props.className)}>
                <div className="container">
                    <PanelArea>
                        <PanelWidget>
                            <ul className="editorHeader-items">
                                {this.props.backUrl && (
                                    <li className="editorHeader-item isPullLeft">
                                        <BackLink
                                            title={t("Cancel")}
                                            url={this.props.backUrl}
                                            visibleLabel={true}
                                            className="editorHeader-backLink"
                                        />
                                    </li>
                                )}
                                <li className="editorHeader-item">
                                    <Button
                                        type="submit"
                                        title={label}
                                        disabled={!this.props.canSubmit}
                                        className={classNames("editorHeader-publish", "buttonPrimary")}
                                    >
                                        {this.props.isSubmitLoading ? <ButtonLoader /> : this.props.callToAction}
                                    </Button>
                                </li>
                                <li className="editorHeader-item">
                                    <h3 id={this.localeTitleID} className="sr-only">
                                        {label}
                                    </h3>
                                    <SelectBox
                                        describedBy={this.localeTitleID}
                                        className="editorHeader-otherLanguages"
                                        buttonClassName="buttonNoBorder buttonNoMinWidth"
                                        buttonBaseClass={ButtonBaseClass.STANDARD}
                                        stickRight={true}
                                    >
                                        {processedChildren}
                                    </SelectBox>
                                </li>
                                <li className="editorHeader-item">
                                    <EditorMenu buttonClassName="editorHeader-menu" />
                                </li>
                            </ul>
                        </PanelWidget>
                    </PanelArea>
                </div>
            </div>
        );
    }
}
