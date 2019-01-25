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
import Paragraph from "@library/components/Paragraph";
import { t } from "@library/application";
import KnowledgeBaseItem from "@knowledge/knowledge-bases/components/KnowledgeBaseItem";
import { LoadStatus } from "@library/@types/api";
import FullPageLoader from "@library/components/FullPageLoader";

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
        const { knowledgeBases, loadStatus } = this.props;

        if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(loadStatus)) {
            return <FullPageLoader />;
        }

        if (knowledgeBases.length < 1) {
            return (
                <div>
                    <Paragraph>{t("No knowledge bases found.")}</Paragraph>
                </div>
            );
        }

        return (
            <ul>
                {knowledgeBases.map(kb => (
                    <li key={kb.knowledgeBaseID}>
                        <KnowledgeBaseItem
                            iconUrl={kb.icon}
                            description={kb.description}
                            title={kb.name}
                            url={kb.url}
                        />
                    </li>
                ))}
            </ul>
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

interface IProps extends ReturnType<typeof mapStateToProps>, ReturnType<typeof mapDispatchToProps> {}

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
