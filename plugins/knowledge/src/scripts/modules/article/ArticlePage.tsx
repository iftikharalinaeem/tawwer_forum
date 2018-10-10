/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { match } from "react-router";
import { connect } from "react-redux";
import { IStoreState } from "@knowledge/state/model";
import { IDeviceProps } from "@library/components/DeviceChecker";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import { LoadStatus } from "@library/@types/api";
import NotFoundPage from "@library/components/NotFoundPage";
import { ArticleLayout } from "@knowledge/modules/article/components";
import PageLoader from "@library/components/PageLoader";
import { IArticlePageState } from "@knowledge/modules/article/ArticlePageReducer";
import ArticlePageActions from "@knowledge/modules/article/ArticlePageActions";
import apiv2 from "@library/apiv2";

interface IProps extends IDeviceProps {
    match: match<{
        id: number;
    }>;
    articlePageState: IArticlePageState;
    articlePageActions: ArticlePageActions;
}

/**
 * Page component for an article.
 */
export class ArticlePage extends React.Component<IProps> {
    /**
     * Render not found or the article.
     */
    public render() {
        const { articlePageState } = this.props;
        const { id } = this.props.match.params;

        if (id === null || (articlePageState.status === LoadStatus.ERROR && articlePageState.error.status === 404)) {
            return <NotFoundPage type="Page" />;
        }

        if (articlePageState.status !== LoadStatus.SUCCESS) {
            return null;
        }

        const { article } = articlePageState.data;

        return (
            <PageLoader {...articlePageState}>
                <ArticleLayout article={article} />
            </PageLoader>
        );
    }

    /**
     * If the component mounts without any data we need to fetch request it.
     */
    public componentDidMount() {
        const { articlePageState, articlePageActions } = this.props;
        const { id } = this.props.match.params;
        if (articlePageState.status !== LoadStatus.PENDING) {
            return;
        }

        if (id === null) {
            return;
        }

        articlePageActions.getArticleByID(id);
    }

    /**
     * When the component unmounts we need to be sure to clear out the data we requested in componentDidMount.
     */
    public componentWillUnmount() {
        this.props.articlePageActions.reset();
    }
}

/**
 * Map in the state from the redux store.
 */
function mapStateToProps(state: IStoreState) {
    return {
        articlePageState: state.knowledge.articlePage,
    };
}

/**
 * Map in action dispatchable action creators from the store.
 */
function mapDispatchToProps(dispatch) {
    return {
        articlePageActions: new ArticlePageActions(dispatch, apiv2),
    };
}

const withRedux = connect(
    mapStateToProps,
    mapDispatchToProps,
);

export default withRedux(withDevice(ArticlePage));
