/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import KnowledgeBaseModel, { KbViewType } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import LocationPickerActions from "@knowledge/modules/locationPicker/LocationPickerActions";
import LocationPickerModel, { ILocationPickerRecord } from "@knowledge/modules/locationPicker/LocationPickerModel";
import { IKbNavigationItem, KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import NavigationSelector from "@knowledge/navigation/state/NavigationSelector";
import { IStoreState } from "@knowledge/state/model";
import { ILoadable, LoadStatus } from "@library/@types/api";
import apiv2 from "@library/apiv2";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import FullPageLoader from "@library/components/FullPageLoader";
import isEqual from "lodash/isEqual";
import * as React from "react";
import { connect } from "react-redux";
import LocationPickerItem from "./LocationPickerItem";
import LocationPickerItemList from "./LocationPickerItemList";

/**
 * Displays the contents of a particular location. Connects NavigationItemList to its data source.
 */
class LocationContents extends React.Component<IProps> {
    private legendRef = React.createRef<HTMLLegendElement>();
    private listID = uniqueIDFromPrefix("navigationItemList");

    public render() {
        const { selectedRecord, childRecords, chosenRecord, title } = this.props;

        const contents =
            childRecords.status === LoadStatus.SUCCESS && childRecords.data ? (
                childRecords.data.map(item => {
                    const isSelected =
                        !!selectedRecord &&
                        item.recordType === selectedRecord.recordType &&
                        item.recordID === selectedRecord.recordID;
                    const isChosen =
                        !!chosenRecord &&
                        item.recordType === chosenRecord.recordType &&
                        item.recordID === chosenRecord.recordID;
                    const navigateHandler = () => this.props.navigateToRecord(item);
                    const selectHandler = () => this.props.selectRecord(item);
                    return (
                        <LocationPickerItem
                            key={item.recordType + item.recordID}
                            isInitialSelection={isChosen}
                            isSelected={isSelected}
                            name={this.radioName}
                            value={item}
                            onNavigate={navigateHandler}
                            onSelect={selectHandler}
                        />
                    );
                })
            ) : (
                <FullPageLoader />
            );
        return (
            <LocationPickerItemList id={this.listID} legendRef={this.legendRef} categoryName={title}>
                {contents}
            </LocationPickerItemList>
        );
    }

    private get radioName(): string {
        return "folders-" + this.listID;
    }

    private setFocusOnLegend() {
        this.legendRef.current!.focus();
    }

    /**
     * @inheritdoc
     */
    public componentDidMount() {
        this.init();
    }

    /**
     * @inheritdoc
     */
    public componentDidUpdate(prevProps: IProps) {
        if (!isEqual(prevProps.navigatedRecord, this.props.navigatedRecord)) {
            this.init();
        }
    }

    private init() {
        this.setFocusOnLegend();
        if (this.props.childRecords.status === LoadStatus.PENDING) {
            void this.props.requestData();
        }
    }
}

interface IOwnProps {}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

interface IMapResult {
    title: string;
    navigatedRecord: ILocationPickerRecord | null;
    selectedRecord: ILocationPickerRecord | null;
    chosenRecord: ILocationPickerRecord | null;
    childRecords: ILoadable<IKbNavigationItem[]>;
}

function mapStateToProps(state: IStoreState, ownProps: IOwnProps): IMapResult {
    const { locationPicker, knowledgeBases, navigation } = state.knowledge;
    const { navigatedRecord, selectedRecord, chosenRecord } = locationPicker;
    const title = LocationPickerModel.selectNavigatedTitle(state);
    const commonReturn = { selectedRecord, chosenRecord, navigatedRecord, title };

    // If nothing is selected we are at the root of the nav picker.
    if (
        !navigatedRecord ||
        knowledgeBases.knowledgeBasesByID.status !== "SUCCESS" ||
        !knowledgeBases.knowledgeBasesByID.data
    ) {
        const kbNavItems = KnowledgeBaseModel.selectKnowledgeBasesAsNavItems(state);

        return {
            ...commonReturn,
            childRecords: {
                ...knowledgeBases.knowledgeBasesByID,
                data: kbNavItems,
            },
        };
    }

    const knowledgeBase = knowledgeBases.knowledgeBasesByID.data[navigatedRecord.knowledgeBaseID];
    const navLoadStatus = navigation.fetchLoadablesByKbID[navigatedRecord.knowledgeBaseID] || {
        status: LoadStatus.PENDING,
    };

    if (navLoadStatus.status === LoadStatus.SUCCESS) {
        let recordKey = navigatedRecord.recordType + navigatedRecord.recordID;
        if (navigatedRecord.recordType === KbRecordType.KB) {
            recordKey = KbRecordType.CATEGORY + knowledgeBase.rootCategoryID;
        }
        const fullNavigatedRecord = navigation.navigationItems[recordKey];
        const recordTypes: KbRecordType[] =
            knowledgeBase.viewType === KbViewType.GUIDE
                ? [KbRecordType.ARTICLE, KbRecordType.CATEGORY]
                : [KbRecordType.CATEGORY];
        if (!fullNavigatedRecord) {
            throw new Error("Attempting to navigate to a record that doesn't exits");
        }

        return {
            ...commonReturn,
            childRecords: {
                ...navLoadStatus,
                data: NavigationSelector.selectDirectChildren(navigation.navigationItems, recordKey, recordTypes),
            },
        };
    } else {
        return {
            ...commonReturn,
            childRecords: {
                ...navLoadStatus,
            },
        };
    }
}

function mapDispatchToProps(dispatch: any) {
    const lpActions = new LocationPickerActions(dispatch, apiv2);

    return {
        requestData: lpActions.requestData,
        chooseRecord: lpActions.chooseRecord,
        selectRecord: lpActions.selectRecord,
        navigateToRecord: lpActions.navigateToRecord,
    };
}

export default connect(
    mapStateToProps,
    mapDispatchToProps,
)(LocationContents);
