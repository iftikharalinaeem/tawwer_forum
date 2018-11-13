/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import classNames from "classnames";
import { LocationBreadcrumbs } from "@knowledge/modules/locationPicker/components";
import Button from "@library/components/forms/Button";
import { t } from "@library/application";
import { Modal } from "@library/components/modal";
import LocationPicker from "@knowledge/modules/locationPicker/LocationPicker";
import { ButtonBaseClass } from "@library/components/forms/Button";
import ModalSizes from "@library/components/modal/ModalSizes";
import LocationPickerModel, { ILPConnectedData } from "@knowledge/modules/locationPicker/LocationPickerModel";
import LocationPickerActions, { ILPActionsProps } from "@knowledge/modules/locationPicker/LocationPickerActions";
import { connect } from "react-redux";
import { plusCircle, categoryIcon } from "@library/components/icons/common";
import ButtonLoader from "@library/components/ButtonLoader";

interface IProps extends ILPActionsProps, ILPConnectedData {
    className?: string;
    initialCategoryID: number | null;
    disabled?: boolean;
    onChange?: (categoryID: number) => void;
}

interface IState {
    showLocationPicker: boolean;
}

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
            <React.Fragment>
                <LocationBreadcrumbs locationData={locationBreadcrumb} icon={categoryIcon("pageLocation-icon")} />
            </React.Fragment>
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
                        {!this.props.disabled && buttonContents}
                        {this.props.disabled && <ButtonLoader />}
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

    private handleChoose = () => {
        this.props.onChange && this.props.onChange(this.value!);
        this.hideLocationPicker();
    };

    public get value(): number {
        return this.props.chosenCategoryID;
    }

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

const withRedux = connect(
    LocationPickerModel.mapStateToProps,
    LocationPickerActions.mapDispatchToProps,
);

export default withRedux(LocationInput);
