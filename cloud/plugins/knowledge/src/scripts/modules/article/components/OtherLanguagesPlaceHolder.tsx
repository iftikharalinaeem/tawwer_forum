/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { panelListClasses } from "@library/layout/panelListStyles";
import { useUniqueID } from "@library/utility/idUtils";
import { useLocaleInfo } from "@vanilla/i18n";
import classNames from "classnames";
import * as React from "react";
import { LoadingRectange, LoadingSpacer } from "@library/loaders/LoadingRectangle";
import { PanelWidget } from "@library/layout/PanelLayout";
import { useLayout } from "@library/layout/LayoutContext";
/**
 * Implements "other languages" DropDown for articles.
 */
export default function OtherLanguagesPlaceHolder() {
    const titleID = useUniqueID("articleOtherLanguages");
    const { currentLocale } = useLocaleInfo();
    const classesPanelList = panelListClasses(useLayout().mediaQueries);

    return (
        <div className={classNames("otherLanguages", "panelList", classesPanelList.root)}>
            <LoadingRectange
                height={20}
                width={"75%"}
                className={classNames("panelList-title", classesPanelList.title)}
                id={titleID}
            />
            <LoadingSpacer height={10} />
            <LoadingRectange height={14} width={"50%"} />
        </div>
    );
}
