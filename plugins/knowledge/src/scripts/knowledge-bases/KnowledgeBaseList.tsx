/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { connect } from "react-redux";
import KnowledgeBaseModel from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { IStoreState } from "@knowledge/state/model";
import KnowledgeBaseActions from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import apiv2 from "@library/apiv2";
import { t } from "@library/application";
import { LoadStatus } from "@library/@types/api";
import FullPageLoader from "@library/components/FullPageLoader";
import WidgetContainer from "@library/components/layouts/components/WidgetContainer";
import SubcommunityList from "@library/components/subcommunities/SubcommunityList";

/**
 * Component representing a list of visible knowledge bases.
 *
 * Capable of handling:
 * - Requesting data.
 * - Displaying loading indicators during fetching.
 * - Data with no items.
 * - Data with items.
 */
class KnowledgeBaseList extends React.Component<IProps> {
    public render() {
        const { knowledgeBases, loadStatus, className } = this.props;

        if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(loadStatus)) {
            return <FullPageLoader />;
        }

        if (LoadStatus.SUCCESS === loadStatus) {
            document.body.classList.remove("isLoading");
        }

        return (
            <WidgetContainer>
                <SubcommunityList
                    title={t("Choose a subcommunity")}
                    titleLevel={1}
                    hiddenTitle={true}
                    items={knowledgeBases}
                    emptyMessage={t("No knowledge bases found.")}
                />
            </WidgetContainer>
        );
    }

    /**
     * Fetch the data on mount if we don't already have it.
     */
    public componentDidMount() {
        if (this.props.loadStatus === LoadStatus.PENDING) {
            this.props.requestKnowledgeBases();
        }
    }
}

interface IProps extends ReturnType<typeof mapStateToProps>, ReturnType<typeof mapDispatchToProps> {
    className?: string;
}

function mapStateToProps(state: IStoreState) {
    return {
        knowledgeBases: KnowledgeBaseModel.selectKnowledgeBases(state),
        loadStatus: state.knowledge.knowledgeBases.knowledgeBasesByID.status,
    };
}

function mapDispatchToProps(dispatch: any) {
    const kbActions = new KnowledgeBaseActions(dispatch, apiv2);
    return {
        requestKnowledgeBases: kbActions.getAll,
    };
}

export default connect(
    mapStateToProps,
    mapDispatchToProps,
)(KnowledgeBaseList);
