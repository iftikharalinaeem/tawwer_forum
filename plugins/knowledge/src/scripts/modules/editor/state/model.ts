/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ILoadable } from "@library/@types/api";
import { IArticle, IArticleRevision } from "@knowledge/@types/api";

export interface IState {
    article: ILoadable<IArticle>;
    revision: ILoadable<IArticleRevision>;
    submit: ILoadable<{}>;
}
