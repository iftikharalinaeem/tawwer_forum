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
import classNames from "classnames";

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

        if (knowledgeBases.length < 1) {
            return (
                <div className={classNames("kbList", className, "isEmpty")}>
                    <Paragraph>{t("No knowledge bases found.")}</Paragraph>
                </div>
            );
        }

        return (
            <div className={classNames("kbList", className)}>
                <ul className="kbList-items">
                    {knowledgeBases.map(kb => (
                        <li key={kb.knowledgeBaseID} className="kbList-item">
                            <KnowledgeBaseItem
                                icon={kb.icon}
                                title={kb.name}
                                description={kb.description}
                                url={kb.url}
                            />
                        </li>
                    ))}
                </ul>
            </div>
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
