/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
import React from "react";
import { connect } from "react-redux";
import { IStoreState } from "@knowledge/state/model";
import KnowledgeBaseModel, {
    KnowledgeBaseDisplayType,
    IKnowledgeBase,
} from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { RouteComponentProps } from "react-router";
import KnowledgeBaseActions from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import apiv2 from "@library/apiv2";
import { LoadStatus } from "@library/@types/api";
import FullPageLoader from "@library/components/FullPageLoader";
import ArticlePage from "@knowledge/modules/article/ArticlePage";
import { t } from "@library/application";
import ErrorPage from "@knowledge/routes/ErrorPage";

class KnowledgeBasePage extends React.Component<IProps> {
    public render(): React.ReactNode {
        const { knowledgeBase, loadStatus } = this.props;

        if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(loadStatus)) {
            return <FullPageLoader />;
        }

        if (knowledgeBase === null) {
            return (
                <ErrorPage
                    loadable={{
                        status: LoadStatus.ERROR,
                        error: { message: t("KnowledgeBase Not Found"), status: 404 },
                    }}
                />
            );
        }

        switch (knowledgeBase.viewType) {
            case KnowledgeBaseDisplayType.GUIDE:
                return this.renderGuide(knowledgeBase);
            case KnowledgeBaseDisplayType.HELP:
                return <div />;
        }
    }

    public componentDidMount() {
        if (this.props.loadStatus === LoadStatus.PENDING) {
            this.props.requestKnowledgeBases();
        }
    }

    private renderGuide(knowledgeBase: IKnowledgeBase): React.ReactNode {
        if (knowledgeBase.defaultArticleID === null) {
            return <div>{t("No articles found. Go create one.")}</div>;
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
