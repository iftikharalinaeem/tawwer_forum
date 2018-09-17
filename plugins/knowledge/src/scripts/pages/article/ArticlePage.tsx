/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { match } from "react-router";
import { connect } from "react-redux";
import { IStoreState, IArticlePageState } from "@knowledge/@types/state";
import { IDeviceProps } from "@knowledge/components/DeviceChecker";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import { LoadStatus } from "@library/@types/api";
import NotFoundPage from "@library/components/NotFoundPage";
import { componentActions } from "@knowledge/pages/article/articlePageActions";
import ArticleLayout from "@knowledge/pages/article/components/ArticleLayout";

interface IProps extends IDeviceProps {
    match: match<{
        id: number;
    }>;
    articlePageState: IArticlePageState;
    getArticle: typeof componentActions.getArticle;
    clearPageState: typeof componentActions.clearArticlePageState;
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

        return <ArticleLayout article={article} />;
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

    /**
     * Parse the ID of the request article out of the slug in the URL.
     *
     * Slugs for an article can be almost anything.
     * The contents of the last trailing slash represent the ID of the resource.
     *
     * @returns The ID or null if an ID could not be parsed out.
     */
    // private parseIDFromSlug(): string | null {
    //     const slug = this.props.match.params.slug;
    //     const idRegex = /.+-(\d+)/;
    //     const id = idRegex.exec(slug);
    //     if (id && id[0]) {
    //         return id[1];
    //     } else {
    //         return null;
    //     }
    // }
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
        getArticle: options => dispatch(componentActions.getArticle(options)),
        clearPageState: () => dispatch(componentActions.clearArticlePageState()),
    };
}

const withRedux = connect(
    mapStateToProps,
    mapDispatchToProps,
);

export default withRedux(withDevice(ArticlePage));
