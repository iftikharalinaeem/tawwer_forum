/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { PanelWidget } from "@library/layout/PanelLayout";
import Heading from "@library/layout/Heading";
import { t } from "@library/utility/appUtils";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import LanguagesDropDown, { ILanguageProps } from "@library/layout/LanguagesDropDown";
import { panelListClasses } from "@library/layout/panelListStyles";
import classNames from "classnames";

export interface IOtherLangaugesProps {
    id?: string;
    selected: any;
    data: ILanguageProps[];
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
        const classesPanelList = panelListClasses();
        const showPicker = this.props.data && this.props.data.length > 1;

        if (showPicker) {
            return (
                <PanelWidget>
                    <div className={classNames("otherLanguages", "panelList", classesPanelList.root)}>
                        <Heading
                            id={this.titleID}
                            title={t("Other Languages")}
                            className={classNames("panelList-title", classesPanelList.title)}
                        />
                        <LanguagesDropDown
                            titleID={this.titleID}
                            widthOfParent={true}
                            className="otherLanguages-select"
                            renderLeft={true}
                            selected={this.props.selected}
                            data={this.props.data}
                        />
                    </div>
                </PanelWidget>
            );
        } else {
            return null;
        }
    }
}
