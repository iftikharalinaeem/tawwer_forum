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
import LocationPickerCategoryItem from "./LocationPickerCategoryItem";
import LocationPickerItemList from "./LocationPickerItemList";
import LocationPickerInsertArticle from "@knowledge/modules/locationPicker/components/LocationPickerInsertArticle";
import LocationPickerArticleItem from "@knowledge/modules/locationPicker/components/LocationPickerArticleItem";
import { inheritHeightClass } from "@library/styles/styleHelpers";
import { style } from "typestyle";
import classNames from "classnames";

/**
 * Displays the contents of a particular location. Connects NavigationItemList to its data source.
 */
class LocationContents extends React.Component<IProps> {
    private legendRef = React.createRef<HTMLLegendElement>();
    private listID = uniqueIDFromPrefix("navigationItemList");

    public render() {
        const { selectedRecord, childRecords, chosenRecord, title } = this.props;

        const currentKBID =
            childRecords && childRecords.data && childRecords.data.length > 0
                ? childRecords.data[0].knowledgeBaseID
                : null;
        const currentKB = currentKBID
            ? {
                  kbViewStyle: "guide", // not sure how to get this
              }
            : null;
        const pickArticleLocation = currentKB && currentKB.kbViewStyle === "guide";

        const setArticleFirstPosition = () => {
            this.setArticleLocation("0");
        };

        let contents;
        if (childRecords.status === LoadStatus.SUCCESS && childRecords.data) {
            if (childRecords.data.length === 0) {
                contents = <LocationPickerInsertArticle onClick={setArticleFirstPosition} key="potentialLocation-0" />;
            } else {
                contents = childRecords.data.map((item, index) => {
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
                    const itemKey = item.recordType + item.recordID;
                    const insertArticleKey = itemKey + "-potentialLocation-" + (index + 1);

                    const setArticlePosition = () => {
                        this.setArticleLocation(index.toString());
                    };

                    const insertArticleFirst =
                        pickArticleLocation && index === 0 ? (
                            <LocationPickerInsertArticle onClick={setArticleFirstPosition} key="potentialLocation-0" />
                        ) : null;

                    if (item.recordType === KbRecordType.ARTICLE) {
                        return (
                            <>
                                {insertArticleFirst}
                                <LocationPickerArticleItem key={itemKey} name={item.name} />
                                <LocationPickerInsertArticle onClick={setArticlePosition} key={insertArticleKey} />
                            </>
                        );
                    } else {
                        return (
                            <>
                                {insertArticleFirst}
                                <LocationPickerCategoryItem
                                    key={itemKey}
                                    isInitialSelection={isChosen}
                                    isSelected={isSelected}
                                    name={this.radioName}
                                    value={item}
                                    onNavigate={navigateHandler}
                                    onSelect={selectHandler}
                                    selectable={!pickArticleLocation}
                                />
                                {pickArticleLocation && (
                                    <LocationPickerInsertArticle onClick={setArticlePosition} key={insertArticleKey} />
                                )}
                            </>
                        );
                    }
                });
            }
        } else {
            contents = (
                <li className={classNames(inheritHeightClass())}>
                    <FullPageLoader />
                </li>
            );
        }

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

    private setArticleLocation(position: string) {
        alert("Article location: " + position);
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
