/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { PanelWidget } from "@knowledge/layouts/PanelLayout";
import Heading from "@library/components/Heading";
import { t } from "@library/application";
import { getRequiredID } from "@library/componentIDs";

import SelectBox, { ISelectboxItem, IExternalLabelledProps } from "@knowledge/modules/common/SelectBox";

export interface IState {
    id: string;
}

export default class ArticleOtherLanguages extends React.Component<IExternalLabelledProps, IState> {
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
        if (this.props.children && this.props.children.length > 1) {
            return (
                <PanelWidget>
                    <div className="otherLanguages">
                        <Heading id={this.titleID} title={t("Other Languages")} />
                        <SelectBox describedBy={this.titleID} selectedIndex={0} className="otherLanguages-select">
                            {this.props.children}
                        </SelectBox>
                    </div>
                </PanelWidget>
            );
        } else {
            return null;
        }
    }
}
