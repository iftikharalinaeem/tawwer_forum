/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { PanelWidget } from "@library/components/layouts/PanelLayout";
import Heading from "@library/components/Heading";
import { t } from "@library/application";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import LanguagesDropDown, { ILanguageProps } from "@library/components/LanguagesDropDown";

export interface IOtherLangaugesProps {
    id?: string;
    selected: any;
    children: ILanguageProps[];
}

/**
 * Implements "other languages" DropDown for articles.
 */
export default class OtherLangauges extends React.Component<IOtherLangaugesProps> {
    private id = uniqueIDFromPrefix("articleOtherLanguages");
    private get titleID() {
        return this.id + "-title";
    }

    public render() {
        const showPicker = this.props.children && this.props.children.length > 1;
        if (showPicker) {
            return (
                <PanelWidget>
                    <div className="otherLanguages panelList">
                        <Heading id={this.titleID} title={t("Other Languages")} className="panelList-title" />
                        <LanguagesDropDown
                            titleID={this.titleID}
                            widthOfParent={true}
                            className="otherLanguages-select"
                            renderLeft={true}
                            selected={this.props.selected}
                        >
                            {this.props.children}
                        </LanguagesDropDown>
                    </div>
                </PanelWidget>
            );
        } else {
            return null;
        }
    }
}
