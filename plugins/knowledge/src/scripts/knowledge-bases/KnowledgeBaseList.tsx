/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useEffect } from "react";
import { connect } from "react-redux";
import KnowledgeBaseModel from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import KnowledgeBaseActions from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import apiv2 from "@library/apiv2";
import { t, getSiteSection } from "@library/utility/appUtils";
import { LoadStatus } from "@library/@types/api/core";
import Loader from "@library/loaders/Loader";

import { knowledgeBaseNoIcon } from "@knowledge/icons/common";
import { tileClasses } from "@library/features/tiles/tileStyles";
import Tiles from "@library/features/tiles/Tiles";
import { useNavHistory } from "@knowledge/navigation/NavHistoryContext";

/**
 * Component representing a list of visible knowledge bases.
 *
 * Capable of handling:
 * - Requesting data.
 * - Displaying loading indicators during fetching.
 * - Data with no items.
 * - Data with items.
 */
function KnowledgeBaseList(props: IProps) {
    const { requestKnowledgeBases, knowledgeBases, loadStatus, className, columns } = props;

    useEffect(() => {
        if (loadStatus === LoadStatus.PENDING) {
            requestKnowledgeBases();
        }
    });

    const { setLastKbID } = useNavHistory();
    useEffect(() => {
        // By Visiting This page there is a likely chance we will navigate to a new knowledge base.
        // To prevent stale navigation state, we should clear the last KB.
        setLastKbID(null);
    });

    const classes = tileClasses();
    if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(loadStatus)) {
        return <Loader />;
    }

    if (LoadStatus.SUCCESS === loadStatus) {
        document.body.classList.remove("isLoading");
    }

    return (
        <Tiles
            title={t("Choose a subcommunity")}
            titleLevel={1}
            hiddenTitle={true}
            items={knowledgeBases.filter(kb => !kb.isUniversalSource)}
            emptyMessage={t("No knowledge bases found.")}
            fallbackIcon={knowledgeBaseNoIcon(classes.fallBackIcon)}
            className={className}
            columns={columns}
        />
    );
}

interface IProps extends ReturnType<typeof mapStateToProps>, ReturnType<typeof mapDispatchToProps> {
    className?: string;
    columns?: number;
}

function mapStateToProps(state: IKnowledgeAppStoreState) {
    const currentSiteSection = getSiteSection();
    return {
        knowledgeBases: KnowledgeBaseModel.selectKnowledgeBases(state).filter(kb => {
            // Make sure the site section group matches.
            return (
                currentSiteSection.sectionGroup === "vanilla" || kb.siteSectionGroup === currentSiteSection.sectionGroup
            );
        }),
        loadStatus: state.knowledge.knowledgeBases.knowledgeBasesByID.status,
    };
}

function mapDispatchToProps(dispatch: any) {
    const kbActions = new KnowledgeBaseActions(dispatch, apiv2);
    return {
        requestKnowledgeBases: kbActions.getAll,
    };
}

export default connect(mapStateToProps, mapDispatchToProps)(KnowledgeBaseList);
