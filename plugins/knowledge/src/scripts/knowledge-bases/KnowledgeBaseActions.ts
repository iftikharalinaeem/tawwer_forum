/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions from "@library/state/ReduxActions";
import { actionCreatorFactory } from "typescript-fsa";
import { asyncFactory } from "typescript-fsa-redux-thunk";

const actionCreator = actionCreatorFactory("@@knowledge-base/");
const thunkCreator = asyncFactory(actionCreator);

export default class KnowledgeBaseActions {

    public static getKnowledgeBasesACs = 

    public static readonly ACTION_TYPES;
}
