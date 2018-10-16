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

interface IProps extends ILPActionsProps, ILPConnectedData {
    className?: string;
    initialCategoryID: number | null;
}

interface IState {
    showLocationPicker: boolean;
}

/**
 * This component allows to display and edit the location of the current page.
 * Creates a location picker in a modal when activated.
 */
export class LocationInput extends React.Component<IProps, IState> {
    private static readonly SELECT_MESSAGE = t("Choose a Category");

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
            <LocationBreadcrumbs locationData={locationBreadcrumb} asString={false} />
        ) : (
            LocationInput.SELECT_MESSAGE
        );

        return (
            <React.Fragment>
                <div className={classNames("pageLocation", this.props.className)}>
                    <span className="pageLocation-label" aria-hidden={true}>
                        {t("To: ")}
                    </span>
                    <Button
                        title={buttonTitle}
                        type="button"
                        aria-label={t("Page Location:")}
                        className="pageLocation"
                        onClick={this.showLocationPicker}
                        baseClass={ButtonBaseClass.CUSTOM}
                    >
                        {buttonContents}
                    </Button>
                </div>
                {this.state.showLocationPicker && (
                    <Modal
                        exitHandler={this.hideLocationPicker}
                        size={ModalSizes.SMALL}
                        className={classNames(this.props.className)}
                        description={t("Choose a location for this page.")}
                    >
                        <LocationPicker
                            onChoose={this.hideLocationPicker}
                            onCloseClick={this.hideLocationPicker}
                            {...passThrough}
                        />
                    </Modal>
                )}
            </React.Fragment>
        );
    }

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
}

const withRedux = connect(
    LocationPickerModel.mapStateToProps,
    LocationPickerActions.mapDispatchToProps,
);

export default withRedux(LocationInput);
