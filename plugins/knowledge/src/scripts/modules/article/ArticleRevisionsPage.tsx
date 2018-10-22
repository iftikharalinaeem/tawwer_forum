/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { match, RouteComponentProps } from "react-router";
import { connect } from "react-redux";
import { IStoreState } from "@knowledge/state/model";
import { IDeviceProps } from "@library/components/DeviceChecker";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import { LoadStatus, ILoadable } from "@library/@types/api";
import NotFoundPage from "@library/components/NotFoundPage";
import ArticleRevisionsLayout from "@knowledge/modules/article/components/ArticleRevisionsLayout";
import PageLoader from "@library/components/PageLoader";
import ArticlePageActions from "@knowledge/modules/article/ArticlePageActions";
import apiv2 from "@library/apiv2";
import DocumentTitle from "@library/components/DocumentTitle";
import { ICrumb } from "@library/components/Breadcrumbs";
import categoryModel from "@knowledge/modules/categories/CategoryModel";
import { IArticle, ArticleStatus, IArticleRevisionFragment, Format, IArticleRevision } from "@knowledge/@types/api";
import ArticleActions, { IArticleActionsProps } from "@knowledge/modules/article/ArticleActions";
import { IUserFragment } from "@dashboard/@types/api/user";
import { t } from "@library/application";
import { getRequiredID } from "@library/componentIDs";
import { IArticleRevisionWithUrl } from "./components/RevisionsList";

interface IOwnProps
    extends RouteComponentProps<{
            id?: number;
        }> {}

interface IProps extends IDeviceProps, IArticleActionsProps, IOwnProps {
    match: match<{
        id: number;
    }>;
    article: ILoadable<IArticle>;
    restoreStatus: LoadStatus;
    articlePageActions: ArticlePageActions;
    breadcrumbData: ICrumb[];
}

interface IState {
    showRestoreDialogue: boolean;
}

/**
 * Page component for an article.
 */
export class ArticleRevisionsPage extends React.Component<IProps, IState> {
    private id;

    constructor(props) {
        super(props);
        id: getRequiredID(props, "articleRevisionPage");
    }

    /**
     * Render not found or the article.
     */
    public render() {
        const { article, breadcrumbData } = this.props;
        const { id } = this.props.match.params;

        if (id === null || (article.status === LoadStatus.ERROR && article.error.status === 404)) {
            return <NotFoundPage type="Page" />;
        }

        return (
            <PageLoader {...article} status={LoadStatus.SUCCESS}>
                <DocumentTitle title={t("Article Revisions")}>
                    <ArticleRevisionsLayout
                        backUrl="#Back"
                        article={article.data!}
                        revisionHistory={revisionHistory()}
                        submitHandler={submitHandler}
                        isSubmitLoading={false}
                    />
                </DocumentTitle>
            </PageLoader>
        );
    }

    /**
     * If the component mounts without data we need to intialize it.
     */
    public componentDidMount() {
        const { article } = this.props;
        if (article.status !== LoadStatus.SUCCESS) {
            this.initializeFromUrl();
        }
    }

    /**
     * If the pages url changes we need to fetch the article data again.
     */
    public componentDidUpdate(prevProps: IProps) {
        if (this.props.match.url !== prevProps.match.url) {
            this.initializeFromUrl();
        }
    }

    /**
     * When the component unmounts we need to be sure to clear out the data we requested in componentDidMount.
     */
    public componentWillUnmount() {
        this.props.articlePageActions.reset();
    }

    /**
     * Initialize the page's data from it's url.
     */
    private initializeFromUrl() {
        const { articlePageActions } = this.props;
        const { id } = this.props.match.params;

        if (id === null) {
            return;
        }

        void articlePageActions.getArticleByID(id);
    }
}

/**
 * Map in the state from the redux store.
 */
function mapStateToProps(state: IStoreState) {
    let breadcrumbData: ICrumb[] | null = null;
    const { article, restoreStatus } = state.knowledge.articlePage;

    if (article.status === LoadStatus.SUCCESS && article.data.knowledgeCategoryID !== null) {
        const categories = categoryModel.selectKbCategoryBreadcrumb(state, article.data.knowledgeCategoryID);
        breadcrumbData = categories.map(category => {
            return {
                name: category.name,
                url: category.url,
            };
        });
    }

    return {
        article,
        breadcrumbData,
        restoreStatus,
    };
}

/**
 * Map in action dispatchable action creators from the store.
 */
function mapDispatchToProps(dispatch) {
    return {
        articlePageActions: new ArticlePageActions(dispatch, apiv2),
        ...ArticleActions.mapDispatchToProps(dispatch),
    };
}

/**
 * Placeholder submit handler
 */

function submitHandler() {
    return;
}

/**
 * Dummy revisions data
 */
function revisionHistory(): IArticleRevisionWithUrl[] {
    const revisionUrl = `/kb/articles/2/revisions?revisionID=1`;
    return [
        {
            articleRevisionID: 1,
            articleID: 1,
            status: "published",
            name: "Some Name",
            format: Format.RICH,
            bodyRendered: "Hello",
            locale: "en",
            insertUser: {
                userID: 1,
                name: "Joe",
                photoUrl: "#",
                dateLastActive: "2019-10-18",
            },
            dateInserted: "2019-10-19",
            url: revisionUrl,
        },
        {
            articleRevisionID: 1,
            articleID: 1,
            status: "published",
            name: "Some Name",
            format: Format.RICH,
            bodyRendered: "Hello",
            locale: "en",
            insertUser: {
                userID: 1,
                name: "Joe",
                photoUrl: "#",
                dateLastActive: "2019-10-18",
            },
            dateInserted: "2019-10-19",
            url: revisionUrl,
        },
        {
            articleRevisionID: 1,
            articleID: 1,
            status: "published",
            name: "Some Name",
            format: Format.RICH,
            bodyRendered: "Hello",
            locale: "en",
            insertUser: {
                userID: 1,
                name: "Joe",
                photoUrl: "#",
                dateLastActive: "2019-10-18",
            },
            dateInserted: "2019-10-19",
            url: revisionUrl,
        },
        {
            articleRevisionID: 1,
            articleID: 1,
            status: "published",
            name: "Some Name",
            format: Format.RICH,
            bodyRendered: "Hello",
            locale: "en",
            insertUser: {
                userID: 1,
                name: "Joe",
                photoUrl: "#",
                dateLastActive: "2019-10-18",
            },
            dateInserted: "2019-10-19",
            url: revisionUrl,
        },
        {
            articleRevisionID: 1,
            articleID: 1,
            status: "published",
            name: "Some Name",
            format: Format.RICH,
            bodyRendered: "Hello",
            locale: "en",
            insertUser: {
                userID: 1,
                name: "Joe",
                photoUrl: "#",
                dateLastActive: "2019-10-18",
            },
            dateInserted: "2019-10-19",
            url: revisionUrl,
        },
        {
            articleRevisionID: 1,
            articleID: 1,
            status: "published",
            name: "Some Name",
            format: Format.RICH,
            bodyRendered: "Hello",
            locale: "en",
            insertUser: {
                userID: 1,
                name: "Joe",
                photoUrl: "#",
                dateLastActive: "2019-10-18",
            },
            dateInserted: "2019-10-19",
            url: revisionUrl,
        },
    ];
}

const withRedux = connect(
    mapStateToProps,
    mapDispatchToProps,
);

export default withRedux(withDevice(ArticleRevisionsPage));
