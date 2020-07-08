/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IArticleLocale } from "@knowledge/@types/api/article";
import SelectBox, { ISelectBoxItem } from "@library/forms/select/SelectBox";
import Heading from "@library/layout/Heading";
import { panelListClasses } from "@library/layout/panelListStyles";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import { t } from "@library/utility/appUtils";
import { uniqueIDFromPrefix, useUniqueID } from "@library/utility/idUtils";
import { LocaleDisplayer, useLocaleInfo } from "@vanilla/i18n";
import classNames from "classnames";
import * as React from "react";
import { WarningIcon } from "@library/icons/common";
import { iconClasses } from "@library/icons/iconStyles";
import { hasPermission, PermissionMode } from "@library/features/users/Permission";
import { useKnowledgeBase } from "@knowledge/knowledge-bases/knowledgeBaseHooks";
import { useLayout } from "@library/layout/LayoutContext";

export interface IOtherLanguagesProps {
    articleLocaleData: IArticleLocale[];
    dateUpdated?: string;
    knowledgeBaseID: number;
}

/**
 * Implements "other languages" DropDown for articles.
 */
export default function OtherLanguages(props: IOtherLanguagesProps) {
    const titleID = useUniqueID("articleOtherLanguages");
    const { currentLocale } = useLocaleInfo();
    const kb = useKnowledgeBase(props.knowledgeBaseID);
    const classesPanelList = panelListClasses(useLayout().mediaQueries);

    const showPicker = props.articleLocaleData && props.articleLocaleData.length > 1;
    if (!showPicker || !currentLocale) {
        return null;
    }

    const options: ISelectBoxItem[] = props.articleLocaleData
        .filter(data => {
            let showArticleLocaleStatus =
                !hasPermission("articles.add", {
                    mode: kb.data?.hasCustomPermission ? PermissionMode.RESOURCE : PermissionMode.GLOBAL,
                    resourceID: props.knowledgeBaseID,
                    resourceType: "knowledgeBase",
                }) && data.translationStatus === "not-translated";
            if (!showArticleLocaleStatus) {
                return data;
            }
        })
        .map((data, index) => {
            return {
                value: data.locale,
                icon: data.translationStatus === "not-translated" && (
                    <ToolTip label={t("This article is not translated yet or it is out of date.")}>
                        <ToolTipIcon>
                            <WarningIcon className={classNames(iconClasses().errorFgColor)} />
                        </ToolTipIcon>
                    </ToolTip>
                ),
                content: <LocaleDisplayer displayLocale={data.locale} localeContent={data.locale} />,
                url: data.url,
                requiresPermission: "articles.add",
                itemStatus: data.translationStatus === "not-translated",
            };
        });
    const showOtherLanguages = options.length > 1;
    const activeOption = options.find(option => option.value === currentLocale);

    return showOtherLanguages ? (
        <nav aria-labelledby={titleID} className={classNames("otherLanguages", "panelList", classesPanelList.root)}>
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
                renderLeft={false}
                offsetPadding={true}
            />
        </nav>
    ) : (
        <></>
    );
}
