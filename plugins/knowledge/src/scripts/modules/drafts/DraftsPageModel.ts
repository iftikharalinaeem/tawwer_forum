/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IResponseArticleDraft } from "@knowledge/@types/api/article";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import ArticleModel from "@knowledge/modules/article/ArticleModel";
import DraftsPageActions from "@knowledge/modules/drafts/DraftsPageActions";
import { IStoreState, KnowledgeReducer } from "@knowledge/state/model";
import { ILoadable, LoadStatus } from "@library/@types/api";
import ReduxReducer from "@library/state/ReduxReducer";
import { produce } from "immer";

export interface IDraftsPageState {
    userDrafts: ILoadable<number[]>;
    deleteDraft: ILoadable<never>;
}

export interface IInjectableDraftsPageProps {
    userDrafts: ILoadable<IResponseArticleDraft[]>;
    deleteDraft: ILoadable<never>;
}

type ReducerType = KnowledgeReducer<IDraftsPageState>;

/**
 *
 */
export default class DraftsPageModel implements ReduxReducer<IDraftsPageState> {
    public initialState: IDraftsPageState = {
        deleteDraft: {
            status: LoadStatus.PENDING,
        },
        userDrafts: {
            status: LoadStatus.PENDING,
        },
    };

    public static mapStateToProps(state: IStoreState): IInjectableDraftsPageProps {
        const { deleteDraft, userDrafts } = state.knowledge.draftsPage;
        const currentUserDrafts: ILoadable<IResponseArticleDraft[]> = {
            status: userDrafts.status,
        };

        currentUserDrafts.data = userDrafts.data
            ? userDrafts.data.map(draftID => ArticleModel.selectDraft(state, draftID)!).filter(draft => draft !== null)
            : undefined;

        return {
            deleteDraft,
            userDrafts: currentUserDrafts,
        };
    }

    public reducer: ReducerType = (state = this.initialState, action) => {
        return produce(state, nextState => {
            if (action.type === DraftsPageActions.RESET) {
                return this.initialState;
            } else if (
                "meta" in action &&
                "identifier" in action.meta &&
                action.meta.identifier === DraftsPageActions.IDENTIFIER
            ) {
                switch (action.type) {
                    case ArticleActions.GET_DRAFTS_ERROR:
                        nextState.userDrafts.status = LoadStatus.ERROR;
                        break;
                    case ArticleActions.GET_DRAFTS_REQUEST:
                        nextState.userDrafts.status = LoadStatus.LOADING;
                        break;
                    case ArticleActions.GET_DRAFTS_RESPONSE:
                        nextState.userDrafts.data = action.payload.data.map(draft => draft.draftID);
                        nextState.userDrafts.status = LoadStatus.SUCCESS;
                        break;
                }
            }
            switch (action.type) {
                case ArticleActions.DELETE_DRAFT_REQUEST:
                    nextState.deleteDraft.status = LoadStatus.LOADING;
                    break;
                case ArticleActions.DELETE_DRAFT_RESPONSE:
                    nextState.deleteDraft.status = LoadStatus.SUCCESS;
                    break;
            }
            return nextState;
        });
    };
}
