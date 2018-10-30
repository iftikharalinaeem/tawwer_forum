/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { PanelWidget } from "@knowledge/layouts/PanelLayout";
import Heading from "@library/components/Heading";
import { t } from "@library/application";
import { getRequiredID } from "@library/componentIDs";
import SelectBox, { ISelectBoxItem } from "@library/components/SelectBox";

interface IState {
    id: string;
}

interface ILanguageProps extends ISelectBoxItem {
    key: string;
    outdated?: boolean;
}

interface IProps {
    id?: string;
    selectedKey: any;
    children: ILanguageProps[];
}

/**
 * Implements "other languages" DropDown for articles.
 */
export default class OtherLangauges extends React.Component<IProps, IState> {
    private get titleID() {
        return this.state.id + "-title";
    }

    public constructor(props) {
        super(props);
        this.state = {
            id: getRequiredID(props, "articleOtherLanguages"),
        };
    }

    public render() {
        const showPicker = this.props.children && this.props.children.length > 1;
        if (showPicker) {
            let foundIndex = false;
            const processedChildren = this.props.children.map(language => {
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
            return (
                <PanelWidget>
                    <div className="otherLanguages panelList">
                        <Heading id={this.titleID} title={t("Other Languages")} className="panelList-title" />
                        <SelectBox
                            describedBy={this.titleID}
                            widthOfParent={true}
                            className="otherLanguages-select"
                            stickRight={true}
                        >
                            {processedChildren}
                        </SelectBox>
                    </div>
                </PanelWidget>
            );
        } else {
            return null;
        }
    }
}
