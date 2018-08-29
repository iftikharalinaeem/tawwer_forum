/**
 * Contains the root AppState interface
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import { IStoreState as IBaseStoreState } from "@dashboard/@types/state";
import { IArticle } from "@knowledge/@types/api";
import { ILoadable } from "@dashboard/@types/api";

export type IArticlePageState = ILoadable<{
    article: IArticle;
}>;

export interface IStoreState extends IBaseStoreState {
    knowledge: {
        articlePage: IArticlePageState;
    };
}
