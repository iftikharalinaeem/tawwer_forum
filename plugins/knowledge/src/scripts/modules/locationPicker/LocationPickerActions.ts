/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import KnowledgeBaseActions from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import CategoryModel from "@knowledge/modules/categories/CategoryModel";
import { ILocationPickerRecord } from "@knowledge/modules/locationPicker/LocationPickerModel";
import NavigationActions from "@knowledge/navigation/state/NavigationActions";
import { IStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api";
import ReduxActions from "@library/state/ReduxActions";
import actionCreatorFactory from "typescript-fsa";
import { IArticle, IKbCategory } from "@knowledge/@types/api";
import { KbRecordType } from "@knowledge/navigation/state/NavigationModel";

const createAction = actionCreatorFactory("@@loationPicker");

/**
 * Actions for the article page.
 */
export default class LocationPickerActions extends ReduxActions {
    /**
     * Initialize the state from a category.
     *
     * @param categoryID The categoryID to initialize with.
     * @param parentID The parent ID of the category.
     */
    public static initAC = createAction<{
        selected: ILocationPickerRecord | null;
        parent: ILocationPickerRecord | null;
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
        const state = this.getState<IStoreState>();
        const { knowledgeBases, navigation, locationPicker } = state.knowledge;
        const requestKBs = async () => {
            const needsKbs = knowledgeBases.knowledgeBasesByID.status === LoadStatus.PENDING;
            if (needsKbs) {
                await this.kbActions.getAll();
            }
        };
        const requestNavigation = async () => {
            const { navigatedRecord } = locationPicker;
            if (navigatedRecord && !navigation.fetchLoadablesByKbID[navigatedRecord.knowledgeBaseID]) {
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
    public initLocationPickerFromRecord = async (record: ILocationPickerRecord) => {
        if (record) {
            const { navigation } = this.getState<IStoreState>().knowledge;
            const { recordID, recordType, knowledgeBaseID } = record;

            if (
                !navigation.fetchLoadablesByKbID[knowledgeBaseID] ||
                navigation.fetchLoadablesByKbID[knowledgeBaseID].status !== LoadStatus.SUCCESS
            ) {
                await this.navActions.getNavigationFlat(knowledgeBaseID);
            }

            const { navigationItems } = this.getState<IStoreState>().knowledge.navigation;
            const ownFullRecord = navigationItems[recordType + recordID];
            if (!ownFullRecord) {
                return;
            }

            const parentRecord = navigationItems[KbRecordType.CATEGORY + ownFullRecord.parentID] || null;
            this.init({ selected: ownFullRecord, parent: parentRecord });
        }
    };
}
