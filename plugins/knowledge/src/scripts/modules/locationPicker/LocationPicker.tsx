/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { t } from "@library/application";
import Button from "@library/components/forms/Button";
import { FramePanel, FrameFooter, FrameBody, FrameHeader, Frame } from "@library/components/frame";
import { newFolder } from "@library/components/icons/common";
import LocationContents from "@knowledge/modules/locationPicker/components/LocationContents";
import NewCategoryForm from "@knowledge/modules/locationPicker/components/NewCategoryForm";
import { IStoreState } from "@knowledge/state/model";
import { connect } from "react-redux";
import LocationPickerModel from "@knowledge/modules/locationPicker/LocationPickerModel";
import CategoryModel from "@knowledge/modules/categories/CategoryModel";
import LocationPickerActions from "@knowledge/modules/locationPicker/LocationPickerActions";
import apiv2 from "@library/apiv2";

/**
 * Component for choosing a location for a new article.
 */
class LocationPicker extends React.Component<IProps, IState> {
    private newFolderButtonRef: React.RefObject<HTMLButtonElement> = React.createRef();
    public state = {
        showNewCategoryModal: false,
    };

    public render() {
        const { actions } = this.props;
        const title = this.props.navigatedCategory ? this.props.navigatedCategory.name : t("Knowledge Bases");

        return (
            <React.Fragment>
                <Frame>
                    <FrameHeader
                        onBackClick={this.canNavigateBack ? this.goBack : undefined}
                        closeFrame={this.props.onCloseClick}
                        title={title}
                    />
                    <FrameBody className="isSelfPadded">
                        <FramePanel>
                            <LocationContents
                                onCategoryNavigate={actions.navigateToCategory}
                                onItemSelect={actions.selectCategory}
                                selectedCategory={this.props.selectedCategory}
                                navigatedCategory={this.props.navigatedCategory}
                                chosenCategory={this.props.choosenCategory}
                                items={this.props.pageContents}
                            />
                        </FramePanel>
                    </FrameBody>
                    <FrameFooter>
                        <Button
                            title={t("New Category")}
                            className="buttonNoBorder isSquare button-pushLeft"
                            onClick={this.showNewCategoryModal}
                            buttonRef={this.newFolderButtonRef}
                        >
                            {newFolder()}
                        </Button>
                        <Button onClick={this.handleChoose} disabled={!this.canChoose} className="buttonPrimary">
                            {t("Choose")}
                        </Button>
                    </FrameFooter>
                </Frame>
                {this.state.showNewCategoryModal && (
                    <NewCategoryForm
                        exitHandler={this.hideNewFolderModal}
                        parentCategoryID={this.props.navigatedCategoryID}
                        buttonRef={this.newFolderButtonRef}
                    />
                )}
            </React.Fragment>
        );
    }

    /**
     * If the current navigated category has a valid parent we can navigate back to it.
     */
    private get canNavigateBack(): boolean {
        return this.props.navigatedCategory !== null;
    }

    /**
     * Navigate one level up in the category hierarchy.
     */
    private goBack = () => {
        if (this.canNavigateBack) {
            void this.props.actions.navigateToCategory({ categoryID: this.props.navigatedCategory!.parentID });
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
        return this.props.selectedCategory !== null;
    }

    private handleChoose = () => {
        if (this.canChoose) {
            this.props.actions.chooseCategory({ categoryID: this.props.selectedCategory!.knowledgeCategoryID });
            this.props.onChoose();
        }
    };

    public componentDidMount() {
        this.forceUpdate();
    }
}

interface IOwnProps {
    className?: string;
    onCloseClick: () => void;
    onChoose: () => void;
}

interface IState {
    showNewCategoryModal: boolean;
}

interface IOwnProps {}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(state: IStoreState, ownProps: IOwnProps) {
    const { navigatedCategoryID, selectedCategoryID, chosenCategoryID } = state.knowledge.locationPicker;
    const navigatedCategory =
        navigatedCategoryID > 0 ? CategoryModel.selectKbCategoryFragment(state, navigatedCategoryID) : null;
    const selectedCategory =
        selectedCategoryID > 0 ? CategoryModel.selectKbCategoryFragment(state, selectedCategoryID) : null;
    const choosenCategory =
        chosenCategoryID > 0 ? CategoryModel.selectKbCategoryFragment(state, chosenCategoryID) : null;

    // Category ID's less than 0 (eg. -1) represents the true root of the forum.
    return {
        pageContents: LocationPickerModel.selectPageContents(state),
        locationBreadcrumb:
            chosenCategoryID > 0 ? CategoryModel.selectKbCategoryBreadcrumb(state, chosenCategoryID) : null,
        navigatedCategory,
        selectedCategory,
        choosenCategory,
        ...state.knowledge.locationPicker,
    };
}

function mapDispatchToProps(dispatch: any) {
    return {
        actions: new LocationPickerActions(dispatch, apiv2),
    };
}

export default connect(
    mapStateToProps,
    mapDispatchToProps,
)(LocationPicker);
