/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import getStore from "@library/redux/getStore";
import rootReducer from "@knowledge/state/reducer";
import { registerReducer } from "@library/redux/reducerRegistry";
import { MockStore } from "@vanilla/redux-utils";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { KB_TEST_INITIAL_STATE } from "@knowledge/__tests__/initialState";
import { DeepPartial } from "redux";

/**
 * Get a mock KB store.
 */
export function getMockKbStore(initialState: DeepPartial<IKnowledgeAppStoreState> = KB_TEST_INITIAL_STATE) {
    registerReducer("knowledge", rootReducer);
    const mockStore = new MockStore<IKnowledgeAppStoreState>(() => getStore(initialState, true));
    return mockStore;
}
