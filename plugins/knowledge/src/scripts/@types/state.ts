/**
 * Contains the root AppState interface
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ILoadable } from "@library/@types/api";
import { IArticle } from "@knowledge/@types/api";

export type IArticlePageState = ILoadable<{
    article: IArticle;
}>;

export interface IStoreState {
    knowledge: {
        articlePage: IArticlePageState;
    };
}
