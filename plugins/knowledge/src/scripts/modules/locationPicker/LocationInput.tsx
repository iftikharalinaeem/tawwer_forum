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
import { IKbCategoryFragment } from "@knowledge/@types/api";

interface IProps extends ILocationPickerProps {
    className?: string;
    initialCategory?: IKbCategoryFragment;
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
                        exitHandler={this.hideLocationPicker}
                        size={ModalSizes.SMALL}
                        className={classNames(this.props.className)}
                        description={t("Choose a location for this page.")}
                    >
                        <LocationPicker onChoose={this.hideLocationPicker} onCloseClick={this.hideLocationPicker} />
                    </Modal>
                )}
            </React.Fragment>
        );
    }

    public get value(): number {
        return this.props.chosenCategoryID;
    }

    public componentDidUpdate(oldProps: IProps) {
        if (oldProps.initialCategory !== this.props.initialCategory && this.props.initialCategory) {
            this.props.initForCategory(this.props.initialCategory);
        }
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
    private hideLocationPicker = () => {
        this.setState({
            showLocationPicker: false,
        });
    };
}

export default withLocationPicker<IProps>(LocationInput);
