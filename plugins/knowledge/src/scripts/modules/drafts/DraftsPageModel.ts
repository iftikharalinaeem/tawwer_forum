/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxReducer from "@library/state/ReduxReducer";
import { ILoadable, LoadStatus } from "@library/@types/api";
import { IResponseArticleDraft } from "@knowledge/@types/api/article";
import DraftsPageActions from "@knowledge/modules/drafts/DraftsPageActions";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import ArticleModel from "@knowledge/modules/article/ArticleModel";
import { produce } from "immer";
import { IStoreState } from "@knowledge/state/model";

export interface IDraftsPageState {
    userDrafts: ILoadable<number[]>;
    deleteDraft: ILoadable<never>;
}

export interface IInjectableDraftsPageProps {
    userDrafts: ILoadable<IResponseArticleDraft[]>;
    deleteDraft: ILoadable<never>;
}

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

    public reducer = (
        state: IDraftsPageState = this.initialState,
        action: typeof DraftsPageActions.ACTION_TYPES | typeof ArticleActions.ACTION_TYPES,
    ): IDraftsPageState => {
        return produce(state, nextState => {
            if (action.type === DraftsPageActions.RESET) {
                return this.initialState;
            } else if (
                action.meta &&
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
