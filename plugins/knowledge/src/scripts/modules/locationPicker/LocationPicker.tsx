/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import LocationContents from "@knowledge/modules/locationPicker/components/LocationContents";
import NewCategoryForm from "@knowledge/modules/locationPicker/components/NewCategoryForm";
import LocationPickerActions from "@knowledge/modules/locationPicker/LocationPickerActions";
import LocationPickerModel from "@knowledge/modules/locationPicker/LocationPickerModel";
import { IStoreState } from "@knowledge/state/model";
import apiv2 from "@library/apiv2";
import { t } from "@library/application";
import Button from "@library/components/forms/Button";
import { Frame, FrameBody, FrameFooter, FrameHeader, FramePanel } from "@library/components/frame";
import { newFolder } from "@library/components/icons/common";
import * as React from "react";
import { connect } from "react-redux";
import { KbRecordType } from "@knowledge/navigation/state/NavigationModel";

/**
 * Component for choosing a location for a new article.
 */
class LocationPicker extends React.Component<IProps, IState> {
    private newFolderButtonRef: React.RefObject<HTMLButtonElement> = React.createRef();
    public state = {
        showNewCategoryModal: false,
    };

    public render() {
        const { navigatedRecord } = this.props;
        return (
            <>
                <Frame>
                    <FrameHeader
                        onBackClick={this.canNavigateBack ? this.goBack : undefined}
                        closeFrame={this.props.onCloseClick}
                        title={this.props.title}
                    />
                    <FrameBody className="isSelfPadded">
                        <FramePanel>
                            <LocationContents />
                        </FramePanel>
                    </FrameBody>
                    <FrameFooter>
                        {navigatedRecord && (
                            <Button
                                title={t("New Category")}
                                className="buttonNoBorder isSquare button-pushLeft"
                                onClick={this.showNewCategoryModal}
                                buttonRef={this.newFolderButtonRef}
                            >
                                {newFolder()}
                            </Button>
                        )}
                        <Button onClick={this.handleChoose} disabled={!this.canChoose} className="buttonPrimary">
                            {t("Choose")}
                        </Button>
                    </FrameFooter>
                </Frame>
                {this.state.showNewCategoryModal && this.props.navigatedCategory && (
                    <NewCategoryForm
                        exitHandler={this.hideNewFolderModal}
                        parentCategoryID={this.props.navigatedCategory.recordID}
                        buttonRef={this.newFolderButtonRef}
                    />
                )}
            </>
        );
    }

    /**
     * If the current navigated category has a valid parent we can navigate back to it.
     */
    private get canNavigateBack(): boolean {
        return !!this.props.navigatedRecord;
    }

    /**
     * Navigate one level up in the category hierarchy.
     */
    private goBack = () => {
        if (this.canNavigateBack) {
            void this.props.navigateToRecord(this.props.parentRecord!);
        }
    };

    /**
     * Display the modal for creating a new category.
     */
    private showNewCategoryModal = e => {
        this.setState({
            showNewCategoryModal: true,
        });
    };

    /**
     * Hide the
     */
    private hideNewFolderModal = () => {
        this.setState({
            showNewCategoryModal: false,
        });
    };

    private get canChoose(): boolean {
        return this.props.selectedRecord !== null;
    }

    private handleChoose = () => {
        if (this.canChoose) {
            this.props.chooseRecord(this.props.selectedRecord!);
            this.props.afterChoose && this.props.afterChoose();
        }
    };

    public componentDidMount() {
        this.forceUpdate();
    }
}

interface IOwnProps {
    className?: string;
    afterChoose: () => void;
    onCloseClick: () => void;
}

interface IState {
    showNewCategoryModal: boolean;
}

interface IOwnProps {}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(state: IStoreState, ownProps: IOwnProps) {
    const { selectedRecord, navigatedRecord } = state.knowledge.locationPicker;
    const navigatedCategory = LocationPickerModel.selectNavigatedCategory(state);
    const parentRecord = LocationPickerModel.selectParentRecord(state);
    const title = LocationPickerModel.selectNavigatedTitle(state);
    return { selectedRecord, navigatedCategory, parentRecord, navigatedRecord, title };
}

function mapDispatchToProps(dispatch: any) {
    const lpActions = new LocationPickerActions(dispatch, apiv2);

    return {
        navigateToRecord: lpActions.navigateToRecord,
        chooseRecord: lpActions.chooseRecord,
    };
}

export default connect(
    mapStateToProps,
    mapDispatchToProps,
)(LocationPicker);
