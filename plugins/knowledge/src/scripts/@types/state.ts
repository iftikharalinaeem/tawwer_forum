/**
 * Contains the root AppState interface
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ILoadable } from "@library/@types/api";
import { IArticle, IArticleRevision } from "@knowledge/@types/api";

export type IArticlePageState = ILoadable<{
    article: IArticle;
}>;

export type IEditorPageState = ILoadable<{
    article?: IArticle;
    revision?: IArticleRevision;
}>;

export interface IStoreState {
    knowledge: {
        articlePage: IArticlePageState;
        addArticlePage: IEditorPageState;
    };
}
