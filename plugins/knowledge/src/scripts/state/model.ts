/**
 * Contains the root AppState interface
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { model as editorModel } from "@knowledge/modules/editor/state";
import { model as articleModel } from "@knowledge/modules/article/state";

export interface IStoreState {
    knowledge: {
        articlePage: articleModel.IState;
        editorPage: editorModel.IState;
    };
}
