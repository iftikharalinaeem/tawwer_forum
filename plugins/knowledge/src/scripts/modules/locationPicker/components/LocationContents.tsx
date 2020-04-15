/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import KnowledgeBaseModel, { KbViewType, IKnowledgeBase } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import LocationPickerArticleItem from "@knowledge/modules/locationPicker/components/LocationPickerArticleItem";
import LocationPickerInsertArticle from "@knowledge/modules/locationPicker/components/LocationPickerInsertArticle";
import LocationPickerInstructions from "@knowledge/modules/locationPicker/components/LocationPickerInstructions";
import LocationPickerActions from "@knowledge/modules/locationPicker/LocationPickerActions";
import LocationPickerModel, { ILocationPickerRecord } from "@knowledge/modules/locationPicker/LocationPickerModel";
import NavigationModel, { IKbNavigationItem, KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import NavigationSelector from "@knowledge/navigation/state/NavigationSelector";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import Loader from "@library/loaders/Loader";
import isEqual from "lodash/isEqual";
import * as React from "react";
import { connect } from "react-redux";
import LocationPickerCategoryItem from "./LocationPickerCategoryItem";
import LocationPickerItemList from "./LocationPickerItemList";
import classNames from "classnames";
import { inheritHeightClass } from "@library/styles/styleHelpers";
import { loaderClasses } from "@library/loaders/loaderStyles";
import Paragraph from "@library/layout/Paragraph";
import { t } from "@library/utility/appUtils";
import LocationPickerEmpty from "@knowledge/modules/locationPicker/components/LocationPickerEmpty";

import LocationPickerSelectedArticle from "./LocationPickerSelectedArticle";

/**
 * Displays the contents of a particular location. Connects NavigationItemList to its data source.
 */
class LocationContents extends React.Component<IProps> {
    private legendRef = React.createRef<HTMLLegendElement>();
    private listID = uniqueIDFromPrefix("navigationItemList");

    public render() {
        const { childRecords, chosenRecord, navigatedRecord, navigatedKB, selectedRecord, title } = this.props;
        const { selectedArticle } = this.props;

        const isSelectedInNavigated =
            navigatedRecord &&
            selectedRecord &&
            navigatedRecord.recordType === selectedRecord.recordType &&
            navigatedRecord.recordID === selectedRecord.recordID;

        const currentSort = selectedRecord && isSelectedInNavigated ? selectedRecord.position : null;
        const canPickArticleLocation = !!navigatedRecord && !!navigatedKB && navigatedKB.viewType === KbViewType.GUIDE;

        const setArticleFirstPosition = () => {
            this.setArticleLocation(0);
        };

        const firstLocationPickerPlaceholder = (
            <>
                <LocationPickerInstructions />
                {selectedArticle && currentSort === 0 ? (
                    <LocationPickerSelectedArticle title={selectedArticle.name} className="isFirst" />
                ) : (
                    <LocationPickerInsertArticle
                        onClick={setArticleFirstPosition}
                        className="isFirst"
                        isSelected={currentSort === 0}
                    />
                )}
            </>
        );
        let contents;
        if (childRecords.status === LoadStatus.SUCCESS && childRecords.data) {
            const recordCount = childRecords.data.length;
            if (recordCount === 0) {
                if (canPickArticleLocation) {
                    contents = firstLocationPickerPlaceholder;
                } else {
                    contents = <LocationPickerEmpty />;
                }
            } else {
                let isPassedSelectedArticle = false;
                contents = childRecords.data.map((item, index) => {
                    const isSelected =
                        (!!selectedRecord &&
                            item.recordType === selectedRecord.recordType &&
                            item.recordID === selectedRecord.recordID) ||
                        (navigatedRecord === null &&
                            item.recordType === KbRecordType.KB &&
                            selectedRecord?.parentID === NavigationModel.SYNTHETIC_ROOT.recordID &&
                            selectedRecord.knowledgeBaseID === item.recordID);
                    const isSelectedArticle =
                        !!selectedArticle &&
                        item.recordID === selectedArticle.articleID &&
                        item.recordType === KbRecordType.ARTICLE;
                    const isChosen =
                        !!chosenRecord &&
                        item.recordType === chosenRecord.recordType &&
                        item.recordID === chosenRecord.recordID;
                    const navigateHandler = () => {
                        this.props.navigateToRecord(item);
                    };
                    const selectHandler = () => this.props.selectRecord(item);
                    const itemKey = item.recordType + item.recordID;
                    let itemSort = item.sort === null ? 0 : item.sort;

                    // Adjust sort offset to handle current article selected.
                    // https://github.com/vanilla/knowledge/issues/988
                    if (isSelectedArticle) {
                        isPassedSelectedArticle = true;
                    }

                    if (!isPassedSelectedArticle) {
                        itemSort++;
                    }

                    const isLast = recordCount === index + 1;
                    const isCurrentLocation = currentSort === itemSort;
                    const nextRecord = !isLast && childRecords.data[index + 1];
                    const isNextSelectedArticle =
                        !!selectedArticle &&
                        nextRecord &&
                        nextRecord.recordID === selectedArticle.articleID &&
                        nextRecord.recordType === KbRecordType.ARTICLE;
                    const shouldRenderInsertButton = !isSelectedArticle && !isNextSelectedArticle;

                    const setArticlePosition = () => {
                        this.setArticleLocation(itemSort);
                    };

                    const isSelectedArticleInCurrentCategory =
                        selectedArticle &&
                        this.props.navigatedRecord &&
                        this.props.navigatedRecord.recordType === KbRecordType.CATEGORY &&
                        selectedArticle.knowledgeCategoryID === this.props.navigatedRecord.recordID;

                    const isSelectedArticleFirst = selectedArticle && selectedArticle.sort === 0;
                    const isSelectedFirstArticleInCurrentCategory =
                        isSelectedArticleInCurrentCategory && isSelectedArticleFirst;
                    const articlePlaceholder =
                        isCurrentLocation && selectedArticle ? (
                            <LocationPickerSelectedArticle
                                title={selectedArticle.name}
                                className={classNames({ isLast })}
                            />
                        ) : (
                            <LocationPickerInsertArticle
                                onClick={setArticlePosition}
                                className={classNames({ isLast })}
                                isSelected={isCurrentLocation}
                            />
                        );
                    const insertArticleFirst =
                        canPickArticleLocation && index === 0 && !isSelectedFirstArticleInCurrentCategory
                            ? firstLocationPickerPlaceholder
                            : null;

                    if (item.recordType === KbRecordType.ARTICLE) {
                        const { selectedArticle } = this.props;
                        if (
                            selectedArticle &&
                            item.recordID === selectedArticle.articleID &&
                            !isCurrentLocation &&
                            selectedRecord &&
                            selectedRecord.position !== undefined &&
                            selectedRecord.position !== selectedArticle.sort
                        ) {
                            return (
                                <LocationPickerInsertArticle
                                    key={itemKey}
                                    onClick={setArticlePosition}
                                    className={classNames({ isLast })}
                                    isSelected={isCurrentLocation}
                                />
                            );
                        } else {
                            return (
                                <React.Fragment key={itemKey}>
                                    {insertArticleFirst}
                                    <LocationPickerArticleItem
                                        name={item.name}
                                        isSelected={!!selectedArticle && item.recordID === selectedArticle.articleID}
                                    />
                                    {shouldRenderInsertButton && articlePlaceholder}
                                </React.Fragment>
                            );
                        }
                    } else {
                        return (
                            <React.Fragment key={itemKey}>
                                {insertArticleFirst}
                                <LocationPickerCategoryItem
                                    isInitialSelection={isChosen}
                                    isSelected={isSelected}
                                    name={this.radioName}
                                    value={item}
                                    onNavigate={navigateHandler}
                                    onSelect={selectHandler}
                                    selectable={!canPickArticleLocation}
                                />
                                {canPickArticleLocation && shouldRenderInsertButton && articlePlaceholder}
                            </React.Fragment>
                        );
                    }
                });
            }
        } else {
            const classesLoader = loaderClasses();
            contents = (
                <li className={inheritHeightClass()}>
                    <div className={classesLoader.loaderContainer(100)}>
                        <Loader loaderStyleClass={classesLoader.mediumLoader} />
                    </div>
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

    private setArticleLocation(position: number) {
        const { navigatedRecord } = this.props;
        if (navigatedRecord) {
            this.props.selectRecord({
                ...navigatedRecord,
                position,
            });
        }
    }
}

interface IOwnProps {}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(state: IKnowledgeAppStoreState, ownProps: IOwnProps) {
    const { locationPicker, knowledgeBases, navigation } = state.knowledge;
    let { navigatedRecord, selectedRecord, chosenRecord, selectedArticle } = locationPicker;
    const title = LocationPickerModel.selectNavigatedTitle(state);

    // Normalize the selected/chosen/navigated records from knowledge bases into categories.
    const kbs = knowledgeBases.knowledgeBasesByID.data;
    const normalizeRecord = (record: ILocationPickerRecord | null): ILocationPickerRecord | null => {
        if (kbs && record && record.recordType === KbRecordType.KB) {
            const fullKb = kbs[record.recordID];
            return {
                ...record,
                recordType: KbRecordType.CATEGORY,
                recordID: fullKb.rootCategoryID,
            };
        } else {
            return record;
        }
    };
    selectedRecord = normalizeRecord(selectedRecord);
    navigatedRecord = normalizeRecord(navigatedRecord)!; // Definitely not null
    selectedRecord = normalizeRecord(selectedRecord);
    const commonReturn = { selectedRecord, chosenRecord, navigatedRecord, title, selectedArticle };

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
            navigatedKB: null,
        };
    }

    const navigatedKB = knowledgeBases.knowledgeBasesByID.data[navigatedRecord.knowledgeBaseID];
    const navLoadStatus = navigation.fetchStatusesByKbID[navigatedRecord.knowledgeBaseID] || LoadStatus.PENDING;

    if (navLoadStatus === LoadStatus.SUCCESS) {
        let recordKey = navigatedRecord.recordType + navigatedRecord.recordID;
        if (navigatedRecord.recordType === KbRecordType.KB) {
            recordKey = KbRecordType.CATEGORY + navigatedKB.rootCategoryID;
        }
        const fullNavigatedRecord = navigation.navigationItems[recordKey];
        const recordTypes: KbRecordType[] =
            navigatedKB.viewType === KbViewType.GUIDE
                ? [KbRecordType.ARTICLE, KbRecordType.CATEGORY]
                : [KbRecordType.CATEGORY];
        if (!fullNavigatedRecord) {
            throw new Error("Attempting to navigate to a record that doesn't exits");
        }

        return {
            ...commonReturn,
            childRecords: {
                status: navLoadStatus,
                data: NavigationSelector.selectDirectChildren(navigation.navigationItems, recordKey, recordTypes),
            },
            navigatedKB,
        };
    } else {
        return {
            ...commonReturn,
            childRecords: {
                status: navLoadStatus,
            },
            navigatedKB,
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

export default connect(mapStateToProps, mapDispatchToProps)(LocationContents);
