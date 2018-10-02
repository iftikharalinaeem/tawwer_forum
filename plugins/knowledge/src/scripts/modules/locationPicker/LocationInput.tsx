/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import classNames from "classnames";
import { LocationBreadcrumbs } from "@knowledge/modules/locationPicker/components";
import Button from "@library/components/forms/Button";
import { withLocationPicker, ILocationPickerProps } from "@knowledge/modules/locationPicker/state/context";
import { t } from "@library/application";
import { Modal, ModalSizes } from "@library/components/modal";
import LocationPicker from "@knowledge/modules/locationPicker/LocationPicker";
import { ButtonBaseClass } from "@library/components/forms/Button";

interface IProps extends ILocationPickerProps {
    className?: string;
}

interface IState {
    showLocationPicker: boolean;
}

/**
 * This component allows to display and edit the location of the current page.
 * Creates a location picker in a modal when activated.
 */
export class LocationInput extends React.Component<IProps, IState> {
    public constructor(props) {
        super(props);
        this.state = {
            showLocationPicker: false,
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
                        aria-label={t("Page Location:")}
                        className="pageLocation"
                        onClick={this.showLocationPicker}
                        baseClass={ButtonBaseClass.CUSTOM}
                    >
                        <LocationBreadcrumbs locationData={locationBreadcrumb} asString={false} />
                    </Button>
                </div>
                {this.state.showLocationPicker && (
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

    /**
     * Cleanup on unmount.
     */
    public componentWillUnmount() {
        this.props.resetNavigation();
    }

    /**
     * Show the location picker modal.
     */
    private showLocationPicker = () => {
        this.props.getKbNavigation({ knowledgeCategoryID: 1 });
        this.setState({
            showLocationPicker: true,
        });
    };

    /**
     * Hiders the location picker modal.
     */
    private hideLocationChooser = () => {
        this.setState({
            showLocationPicker: false,
        });
    };
}

export default withLocationPicker(LocationInput);
