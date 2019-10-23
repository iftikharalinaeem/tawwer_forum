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
import { panelListClasses } from "@library/layout/panelListStyles";
import classNames from "classnames";
import { IArticleLocale } from "@knowledge/@types/api/article";
import { useUniqueID } from "@library/utility/idUtils";
import SelectBox, { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { useLocaleInfo, LocaleDisplayer, ILocale, loadLocales } from "@vanilla/i18n";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { AlertIcon } from "@library/icons/common";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";

export interface IOtherLangaugesProps {
    articleLocaleData: IArticleLocale[];
    languageSelect?: boolean;
    dateUpdated?: string;
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
    let selectedIndex = 0;
    const selectBoxItems: ISelectBoxItem[] = props.articleLocaleData.map((data, index) => {
        const isSelected = data.locale === currentLocale;
        if (isSelected) {
            selectedIndex = index;
        }
        return {
            selected: isSelected,
            name: data.locale,
            icon: data.translationStatus === "not-translated" && <AlertIcon className={"selectBox-selectedIcon"} />,
            content: (
                <>
                    {data.translationStatus !== "not-translated" && (
                        <LocaleDisplayer displayLocale={data.locale} localeContent={data.locale} />
                    )}
                    {data.translationStatus === "not-translated" && (
                        <ToolTip
                            label={`${(
                                <Translate
                                    source="This article was editied in its source locale on <0/>. Edit this article to update its translation and clear this meesage."
                                    c0={<DateTime timestamp={props.dateUpdated} />}
                                />
                            )}`}
                        >
                            <span>
                                <LocaleDisplayer displayLocale={data.locale} localeContent={data.locale} />
                            </span>
                        </ToolTip>
                    )}
                </>
            ),
            onClick: () => {
                window.location.href = data.url;
            },
        };
    });

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
                    data={props.articleLocaleData}
                    currentLocale={currentLocale}
                    dateUpdated={props.dateUpdated}
                    selcteBoxItems={selectBoxItems}
                    selectedIndex={selectedIndex}
                />
            </div>
        </PanelWidget>
    );
}
