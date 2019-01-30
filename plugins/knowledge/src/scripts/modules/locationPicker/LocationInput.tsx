/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import classNames from "classnames";
import LocationBreadcrumbs from "@knowledge/modules/locationPicker/components/LocationBreadcrumbs";
import Button from "@library/components/forms/Button";
import { t } from "@library/application";
import { Modal } from "@library/components/modal";
import LocationPicker from "@knowledge/modules/locationPicker/LocationPicker";
import { ButtonBaseClass } from "@library/components/forms/Button";
import ModalSizes from "@library/components/modal/ModalSizes";
import LocationPickerActions from "@knowledge/modules/locationPicker/LocationPickerActions";
import { connect } from "react-redux";
import { plusCircle, categoryIcon } from "@library/components/icons/common";
import { IStoreState } from "@knowledge/state/model";
import apiv2 from "@library/apiv2";
import CategoryModel from "@knowledge/modules/categories/CategoryModel";

/**
 * This component allows to display and edit the location of the current page.
 * Creates a location picker in a modal when activated.
 */
export class LocationInput extends React.PureComponent<IProps, IState> {
    private changeLocationButton: React.RefObject<HTMLButtonElement> = React.createRef();
    private static readonly SELECT_MESSAGE = t("Select a Category");

    public state: IState = {
        showLocationPicker: false,
    };

    public render() {
        const { className, ...passThrough } = this.props;
        const { locationBreadcrumb } = this.props;
        const buttonTitle = locationBreadcrumb
            ? LocationBreadcrumbs.renderString(locationBreadcrumb)
            : LocationInput.SELECT_MESSAGE;

        const buttonContents = locationBreadcrumb ? (
            <LocationBreadcrumbs locationData={locationBreadcrumb} icon={categoryIcon("pageLocation-icon")} />
        ) : (
            <React.Fragment>
                {plusCircle("pageLocation-icon")}
                {LocationInput.SELECT_MESSAGE}
            </React.Fragment>
        );

        return (
            <React.Fragment>
                <div className={classNames("pageLocation", this.props.className)}>
                    <Button
                        title={buttonTitle}
                        type="button"
                        aria-label={t("Page Location")}
                        className="pageLocation-picker"
                        onClick={this.showLocationPicker}
                        baseClass={ButtonBaseClass.CUSTOM}
                        buttonRef={this.changeLocationButton}
                        disabled={!!this.props.disabled}
                    >
                        {buttonContents}
                    </Button>
                </div>
                {this.state.showLocationPicker && (
                    <Modal
                        exitHandler={this.hideLocationPicker}
                        size={ModalSizes.SMALL}
                        className={classNames(this.props.className)}
                        label={t("Choose a location for this page.")}
                        elementToFocusOnExit={this.changeLocationButton.current!}
                    >
                        <LocationPicker
                            onChoose={this.handleChoose}
                            onCloseClick={this.hideLocationPicker}
                            {...passThrough}
                        />
                    </Modal>
                )}
            </React.Fragment>
        );
    }

    public componentDidMount() {
        if (this.props.initialCategoryID !== null) {
            this.props.initLocationPickerFromCategoryID(this.props.initialCategoryID);
        }
    }

    private handleChoose = () => {
        this.props.onChange && this.props.onChange(this.props.selectedCategoryID!);
        this.hideLocationPicker();
    };

    /**
     * Show the location picker modal.
     */
    private showLocationPicker = () => {
        this.setState({
            showLocationPicker: true,
        });
    };

    /**
     * Hiders the location picker modal.
     */
    private hideLocationPicker = () => {
        this.setState({
            showLocationPicker: false,
        });
    };

    public componentDidUpdate(prevProps, prevState) {
        if (prevState.showLocationPicker !== this.state.showLocationPicker) {
            this.forceUpdate();
        }
    }
}

interface IOwnProps {
    className?: string;
    initialCategoryID: number | null;
    disabled?: boolean;
    onChange?: (categoryID: number) => void;
}

interface IState {
    showLocationPicker: boolean;
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(state: IStoreState, ownProps: IOwnProps) {
    const { chosenCategoryID, selectedCategoryID } = state.knowledge.locationPicker;
    return {
        selectedCategoryID,
        locationBreadcrumb:
            chosenCategoryID > 0 ? CategoryModel.selectKbCategoryBreadcrumb(state, chosenCategoryID) : null,
    };
}

function mapDispatchToProps(dispatch: any) {
    const lpActions = new LocationPickerActions(dispatch, apiv2);
    return {
        initLocationPickerFromCategoryID: lpActions.initLocationPickerFromCategoryID,
    };
}

export default connect(
    mapStateToProps,
    mapDispatchToProps,
)(LocationInput);
