/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
import HelpCenterHome from "@knowledge/helpCenter/HelpCenterHome";
import KnowledgeBaseActions from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import KnowledgeBaseModel, { IKnowledgeBase, KbViewType } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import ArticlePage from "@knowledge/modules/article/ArticlePage";
import ErrorPage, { DefaultError } from "@knowledge/routes/ErrorPage";
import { IStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api";
import apiv2 from "@library/apiv2";
import FullPageLoader from "@library/components/FullPageLoader";
import React from "react";
import { connect } from "react-redux";
import { RouteComponentProps } from "react-router";

class KnowledgeBasePage extends React.Component<IProps> {
    public render(): React.ReactNode {
        const { knowledgeBase, loadStatus } = this.props;

        if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(loadStatus)) {
            return <FullPageLoader />;
        }

        if (knowledgeBase === null) {
            return <ErrorPage defaultError={DefaultError.NOT_FOUND} />;
        }

        switch (knowledgeBase.viewType) {
            case KbViewType.GUIDE:
                return this.renderGuide(knowledgeBase);
            case KbViewType.HELP:
                return <HelpCenterHome knowledgeBase={knowledgeBase} />;
        }
    }

    public componentDidMount() {
        if (this.props.loadStatus === LoadStatus.PENDING) {
            this.props.requestKnowledgeBases();
        }
    }

    private renderGuide(knowledgeBase: IKnowledgeBase): React.ReactNode {
        if (knowledgeBase.defaultArticleID === null) {
            return (
                <ErrorPage defaultError={DefaultError.NO_ARTICLES} knowledgeBaseID={knowledgeBase.knowledgeBaseID} />
            );
        }

        const match = {
            ...this.props.match,
            params: {
                id: knowledgeBase.defaultArticleID.toString(),
            },
        };
        return <ArticlePage match={match} />;
    }
}

interface IOwnProps extends RouteComponentProps<{ urlCode: string }> {}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(state: IStoreState, ownProps: IOwnProps) {
    const knowledgeBase = KnowledgeBaseModel.selectByUrlCode(state, ownProps.match.params.urlCode);
    return {
        knowledgeBase,
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
)(KnowledgeBasePage);
