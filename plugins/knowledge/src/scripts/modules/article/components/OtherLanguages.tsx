/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IArticleLocale } from "@knowledge/@types/api/article";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import SelectBox, { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { AlertIcon } from "@library/icons/common";
import Heading from "@library/layout/Heading";
import { panelListClasses } from "@library/layout/panelListStyles";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@library/utility/appUtils";
import { useUniqueID } from "@library/utility/idUtils";
import { LocaleDisplayer, useLocaleInfo } from "@vanilla/i18n";
import classNames from "classnames";
import * as React from "react";

export interface IOtherLangaugesProps {
    articleLocaleData: IArticleLocale[];
    dateUpdated?: string;
}

/**
 * Implements "other languages" DropDown for articles.
 */
export default function OtherLangauges(props: IOtherLangaugesProps) {
    const titleID = useUniqueID("articleOtherLanguages");
    const { currentLocale } = useLocaleInfo();
    const classesPanelList = panelListClasses();

    const showPicker = props.articleLocaleData && props.articleLocaleData.length > 1;
    if (!showPicker || !currentLocale) {
        return null;
    }
    const options: ISelectBoxItem[] = props.articleLocaleData.map((data, index) => {
        return {
            value: data.locale,
            name: data.locale,
            icon: data.translationStatus === "not-translated" && (
                <ToolTip
                    label={
                        <Translate
                            source="This article was edited in source locale on <0/>. Edit this article to update its translation and clear this message."
                            c0={<DateTime timestamp={props.dateUpdated} />}
                        />
                    }
                    ariaLabel={"This article was editied in its source locale."}
                >
                    <span>
                        <AlertIcon className={"selectBox-selectedIcon"} />
                    </span>
                </ToolTip>
            ),
            content: <LocaleDisplayer displayLocale={data.locale} localeContent={data.locale} />,
            url: data.url,
        };
    });

    const activeOption = options.find(option => option.value === currentLocale);

    return (
        <div className={classNames("otherLanguages", "panelList", classesPanelList.root)}>
            <Heading
                title={t("Other Languages")}
                className={classNames("panelList-title", classesPanelList.title)}
                id={titleID}
            />
            <SelectBox
                widthOfParent={true}
                className="otherLanguages-select"
                options={options}
                describedBy={titleID}
                value={activeOption}
            />
        </div>
    );
}
