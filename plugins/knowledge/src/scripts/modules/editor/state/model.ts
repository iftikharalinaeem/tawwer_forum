/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { ILoadable } from "@library/@types/api";
import { IArticle, IArticleRevision } from "@knowledge/@types/api";

export interface IState {
    article: ILoadable<IArticle>;
    revision: ILoadable<IArticleRevision>;
    submit: ILoadable<{}>;
}
