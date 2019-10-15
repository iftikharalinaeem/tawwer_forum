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
import { useLocaleInfo, ILocale } from "@vanilla/i18n";
import { panelListClasses } from "@library/layout/panelListStyles";
import classNames from "classnames";
import BlockquoteLineBlot from "@rich-editor/quill/blots/blocks/BlockquoteBlot";

export interface IOtherLangaugesProps {
    id?: string;
    selected: any;
    data: ILanguageProps[];
    useLocaleInfo?: {
        [key: string]: ILocale[];
    };
    currentLocale?: string;
    languageSelect?: boolean;
}

/**
 * Implements "other languages" DropDown for articles.
 */

export default function OtherLangauges(this: any, props: IOtherLangaugesProps) {
    /*  private id = uniqueIDFromPrefix("articleOtherLanguages");
    private get titleID() {
        return this.id + "-title";
    }*/

    const classesPanelList = panelListClasses();
    const showPicker = props.data && props.data.length > 1;
    const { locales, currentLocale } = useLocaleInfo();
    if (showPicker) {
        return (
            <PanelWidget>
                <div className={classNames("otherLanguages", "panelList", classesPanelList.root)}>
                    <Heading
                        // id={titleID}
                        title={t("Other Languages")}
                        className={classNames("panelList-title", classesPanelList.title)}
                    />
                    <LanguagesDropDown
                        titleID={props.id}
                        widthOfParent={true}
                        className="otherLanguages-select"
                        renderLeft={true}
                        selected={props.selected}
                        data={props.data}
                        useLocaleInfo={locales}
                        currentLocale={currentLocale}
                        languageSelect={true}
                    />
                </div>
            </PanelWidget>
        );
    } else {
        return null;
    }
}
