/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { PanelWidget } from "@library/layout/PanelLayout";
import Heading from "@library/layout/Heading";
import { t } from "@library/dom/appUtils";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import LanguagesDropDown, { ILanguageProps } from "@library/layout/LanguagesDropDown";

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
