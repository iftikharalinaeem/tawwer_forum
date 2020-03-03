/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import HelpCenterNavigation from "@knowledge/helpCenter/components/HelpCenterNavigation";
import { IKnowledgeBase, IKnowledgeBaseFragment, KbViewType } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import { NavLinksPlaceholder } from "@vanilla/library/src/scripts/navigation/NavLinksPlaceholder";
import React from "react";
import { useKnowledgeBase, useHelpCenterNavigation } from "@knowledge/knowledge-bases/knowledgeBaseHooks";
import Tiles from "@vanilla/library/src/scripts/features/tiles/Tiles";
import { t } from "@vanilla/i18n";
import { knowledgeBaseNoIcon } from "@knowledge/icons/common";
import { tileClasses } from "@vanilla/library/src/scripts/features/tiles/tileStyles";

interface IProps {
    kb: IKnowledgeBase;
}

export function UniversalKnowledgeWidget(props: IProps) {
    const { kb } = props;
    const { universalSources } = kb;

    const guides: IKnowledgeBaseFragment[] = [];
    const helpCenters: IKnowledgeBaseFragment[] = [];

    universalSources?.forEach(source => {
        switch (source.viewType) {
            case KbViewType.GUIDE:
                guides.push(source);
                break;
            case KbViewType.HELP:
                helpCenters.push(source);
                break;
        }
    });

    return (
        <>
            {guides.length > 0 && (
                <Tiles
                    emptyMessage=""
                    title={t("Guides")}
                    fallbackIcon={knowledgeBaseNoIcon(tileClasses().fallBackIcon)}
                    items={guides.map(guide => {
                        return {
                            icon: guide.icon,
                            name: guide.name,
                            description: guide.description,
                            url: guide.url,
                        };
                    })}
                />
            )}
            {helpCenters.map(helpCenter => {
                return <UniversalHelpCenterNav key={helpCenter.knowledgeBaseID} kbID={helpCenter.knowledgeBaseID} />;
            })}
        </>
    );
}

export function UniversalHelpCenterNav(props: { kbID: number }) {
    const { kbID } = props;
    const kb = useKnowledgeBase(props.kbID);
    const nav = useHelpCenterNavigation(kbID);

    if (nav.status === LoadStatus.ERROR || nav.error || kb.status === LoadStatus.ERROR || kb.error) {
        // No error state designed right now.
        return null;
    }

    if (!kb.data) {
        return <NavLinksPlaceholder title={""} />;
    }

    const loadingStatuses = [LoadStatus.PENDING, LoadStatus.LOADING];
    if (loadingStatuses.includes(kb.status) || loadingStatuses.includes(nav.status) || !nav.data || !kb.data) {
        return <NavLinksPlaceholder title={kb.data.name} />;
    }

    return (
        <HelpCenterNavigation
            data={nav.data.navigation}
            title={kb.data.name}
            showTitle
            rootCategoryUrl={nav.data.rootCategoryUrl}
        />
    );
}
