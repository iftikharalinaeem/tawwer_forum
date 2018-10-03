/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { bindActionCreators } from "redux";
import { match } from "react-router";
import { connect } from "react-redux";
import { IStoreState } from "@knowledge/state/model";
import { IDeviceProps } from "@library/components/DeviceChecker";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import { LoadStatus } from "@library/@types/api";
import NotFoundPage from "@library/components/NotFoundPage";
import { actions, thunks, model } from "@knowledge/modules/article/state";
import { ArticleLayout, ArticleMenu } from "@knowledge/modules/article/components";
import PageLoader from "@library/components/PageLoader";

interface IProps extends IDeviceProps {
    match: match<{
        id: number;
    }>;
    articlePageState: model.IState;
    getArticle: typeof thunks.getArticle;
    clearPageState: typeof actions.clearPageState;
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
        const { articlePageState, getArticle } = this.props;
        const { id } = this.props.match.params;
        if (articlePageState.status !== LoadStatus.PENDING) {
            return;
        }

        if (id === null) {
            return;
        }

        getArticle(id);
    }

    /**
     * When the component unmounts we need to be sure to clear out the data we requested in componentDidMount.
     */
    public componentWillUnmount() {
        this.props.clearPageState();
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
    const { getArticle } = thunks;
    const { clearPageState } = actions;
    return bindActionCreators({ getArticle, clearPageState }, dispatch);
}

const withRedux = connect(
    mapStateToProps,
    mapDispatchToProps,
);

export default withRedux(withDevice(ArticlePage));
