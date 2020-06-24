/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import KnowledgeBaseActions from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import { ILocationPickerRecord } from "@knowledge/modules/locationPicker/LocationPickerModel";
import NavigationActions from "@knowledge/navigation/state/NavigationActions";
import { KbRecordType, IKbNavigationItem } from "@knowledge/navigation/state/NavigationModel";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api/core";
import ReduxActions from "@library/redux/ReduxActions";
import actionCreatorFactory from "typescript-fsa";
import KnowledgeBaseModel from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { IArticle } from "@knowledge/@types/api/article";

const createAction = actionCreatorFactory("@@loationPicker");

/**
 * Actions for the article page.
 */
export default class LocationPickerActions extends ReduxActions<IKnowledgeAppStoreState> {
    /**
     * Initialize the state from a category.
     *
     * @param categoryID The categoryID to initialize with.
     * @param parentID The parent ID of the category.
     */
    public static initAC = createAction<{
        selectedCategory: ILocationPickerRecord | null;
        parentCategory: ILocationPickerRecord | null;
        article: IArticle | null;
    }>("init");

    /**
     * Set the navigated category.
     *
     * @param categoryID
     */
    public static navigateAC = createAction<ILocationPickerRecord | null>("navigate");

    /**
     * Set the selected category.
     *
     * @param categoryID
     */
    public static selectAC = createAction<ILocationPickerRecord>("select");

    /**
     * Set the chosen category.
     *
     * @param categoryID
     */
    public static chooseAC = createAction<ILocationPickerRecord>("choose");

    // Bind dispatch the simpler action creators instead of rewriting their function signatures.
    public selectRecord = this.bindDispatch(LocationPickerActions.selectAC);
    public chooseRecord = this.bindDispatch(LocationPickerActions.chooseAC);
    public navigateToRecord = this.bindDispatch(LocationPickerActions.navigateAC);
    public init = this.bindDispatch(LocationPickerActions.initAC);

    private navActions = new NavigationActions(this.dispatch, this.api, this.getState);
    private kbActions = new KnowledgeBaseActions(this.dispatch, this.api, this.getState);

    public requestData = async () => {
        const state = this.getState();
        const { knowledgeBases, navigation, locationPicker } = state.knowledge;
        const requestKBs = async () => {
            const needsKbs = knowledgeBases.knowledgeBasesByID.status === LoadStatus.PENDING;
            if (needsKbs) {
                await this.kbActions.getAll();
            }
        };
        const requestNavigation = async () => {
            const { navigatedRecord } = locationPicker;
            if (navigatedRecord && !navigation.fetchStatusesByKbID[navigatedRecord.knowledgeBaseID]) {
                await this.navActions.getNavigationFlat(navigatedRecord.knowledgeBaseID);
            }
        };

        await Promise.all([requestKBs(), requestNavigation()]);
    };

    /**
     * Initialize location picker actions from an article.
     *
     * @param article The article to init from.
     */
    public initLocationPickerFromRecord = async (record: ILocationPickerRecord, article: IArticle | null) => {
        if (record) {
            const { knowledgeBases, navigation } = this.getState().knowledge;
            const { recordID, recordType, knowledgeBaseID, articleRedirection } = record;

            if (
                !navigation.fetchStatusesByKbID[knowledgeBaseID] ||
                navigation.fetchStatusesByKbID[knowledgeBaseID] !== LoadStatus.SUCCESS
            ) {
                await this.navActions.getNavigationFlat(knowledgeBaseID);
            }

            const { navigationItems } = this.getState().knowledge.navigation;
            const ownFullRecord = navigationItems[recordType + recordID];
            if (!ownFullRecord) {
                return;
            }

            // Determine if the navigated category is actually a KB root category. If it is, select the KB, instead.
            let ownKnowledgeBase: IKbNavigationItem | null = null;
            if (
                ownFullRecord.recordType === KbRecordType.CATEGORY &&
                ownFullRecord.parentID === -1 &&
                knowledgeBases.knowledgeBasesByID &&
                knowledgeBases.knowledgeBasesByID.status === LoadStatus.SUCCESS &&
                knowledgeBases.knowledgeBasesByID.data &&
                knowledgeBases.knowledgeBasesByID.data[ownFullRecord.knowledgeBaseID]
            ) {
                const recordKnowledgeBase = knowledgeBases.knowledgeBasesByID.data[ownFullRecord.knowledgeBaseID];
                ownKnowledgeBase = {
                    recordType: KbRecordType.KB,
                    recordID: recordKnowledgeBase.knowledgeBaseID,
                    knowledgeBaseID: recordKnowledgeBase.knowledgeBaseID,
                    name: recordKnowledgeBase.name,
                    url: recordKnowledgeBase.url,
                    parentID: -1,
                    sort: null,
                };
            }

            const parentRecord = navigationItems[KbRecordType.CATEGORY + ownFullRecord.parentID] || null;
            const selectedCategory = ownKnowledgeBase || ownFullRecord;
            this.init({ selectedCategory, parentCategory: parentRecord, article });
        }
    };
}
