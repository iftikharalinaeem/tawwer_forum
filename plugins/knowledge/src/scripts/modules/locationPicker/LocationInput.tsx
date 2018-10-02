/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { LocationBreadcrumbs } from "@knowledge/modules/locationPicker/components";
import Button from "@dashboard/components/forms/Button";
import { withLocationPicker, ILocationPickerProps } from "@knowledge/modules/locationPicker/state/context";
import { t } from "@library/application";
import Modal, { ModalSizes } from "@knowledge/components/Modal";
import LocationPicker from "@knowledge/modules/locationPicker/LocationPicker";

interface IProps extends ILocationPickerProps {
    className?: string;
}

interface IState {
    showLocationChooser: boolean;
}

/**
 * This component allows to display and edit the location of the current page.
 * Calls the LocationChooser component when clicked.
 */
export class LocationInput extends React.Component<IProps, IState> {
    public constructor(props) {
        super(props);
        this.state = {
            showLocationChooser: false,
        };
    }

    public render() {
        const { locationBreadcrumb } = this.props;

        return (
            <React.Fragment>
                <div className={classNames("pageLocation", this.props.className)}>
                    <span className="pageLocation-label" aria-hidden={true}>
                        {t("To: ")}
                    </span>
                    <Button
                        title={LocationBreadcrumbs.renderString(locationBreadcrumb)}
                        type="button"
                        aria-label={t("Article Location:")}
                        className="pageLocation"
                        onClick={this.showLocationChooser}
                    >
                        <LocationBreadcrumbs locationData={locationBreadcrumb} asString={false} />
                    </Button>
                </div>
                {this.state.showLocationChooser && (
                    <Modal
                        exitHandler={this.hideLocationChooser}
                        size={ModalSizes.SMALL}
                        className={classNames(this.props.className)}
                        description={t("Choose a location for this page.")}
                    >
                        <LocationPicker onCloseClick={this.hideLocationChooser} />
                    </Modal>
                )}
            </React.Fragment>
        );
    }

    public componentWillUnmount() {
        this.props.resetNavigation();
    }

    private showLocationChooser = () => {
        this.props.getKbNavigation({ knowledgeCategoryID: 1 });
        this.setState({
            showLocationChooser: true,
        });
    };

    private hideLocationChooser = () => {
        this.setState({
            showLocationChooser: false,
        });
    };
}

export default withLocationPicker(LocationInput);
