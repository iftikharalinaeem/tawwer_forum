/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { PanelWidget } from "@library/layout/PanelLayout";
import Heading from "@library/layout/Heading";
import { t } from "@library/utility/appUtils";
import LanguagesDropDown from "@library/layout/LanguagesDropDown";
import { useLocaleInfo } from "@vanilla/i18n";
import { panelListClasses } from "@library/layout/panelListStyles";
import classNames from "classnames";
import { IArticleLocale } from "@knowledge/@types/api/article";
import { useUniqueID } from "@library/utility/idUtils";

export interface IOtherLangaugesProps {
    articleLocaleData: IArticleLocale[];
    languageSelect?: boolean;
}

/**
 * Implements "other languages" DropDown for articles.
 */

export default function OtherLangauges(props: IOtherLangaugesProps) {
    const id = useUniqueID("articleOtherLanguages");
    const { currentLocale } = useLocaleInfo();

    const classesPanelList = panelListClasses();
    const showPicker = props.articleLocaleData && props.articleLocaleData.length > 1;
    if (!showPicker || !currentLocale) {
        return null;
    }

    return (
        <PanelWidget>
            <div className={classNames("otherLanguages", "panelList", classesPanelList.root)}>
                <Heading
                    title={t("Other Languages")}
                    className={classNames("panelList-title", classesPanelList.title)}
                />
                <LanguagesDropDown
                    titleID={id}
                    widthOfParent={true}
                    className="otherLanguages-select"
                    renderLeft={true}
                    data={props.articleLocaleData}
                    currentLocale={currentLocale}
                />
            </div>
        </PanelWidget>
    );
}
