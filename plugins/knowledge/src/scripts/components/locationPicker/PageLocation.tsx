/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/application";
import classNames from "classnames";
import { IBreadcrumbsProps } from "../Breadcrumbs";
import LocationPicker from "@knowledge/components/locationPicker/LocationPicker";
import Button from "@dashboard/components/forms/Button";
import { withLocationPicker, ILocationPickerProps } from "@knowledge/modules/locationPicker/state/context";
import LocationBreadcrumbs from "@knowledge/components/locationPicker/LocationBreadcrumbs";

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
export class PageLocation extends React.Component<IProps, IState> {
    public constructor(props) {
        super(props);
        this.state = {
            showLocationChooser: false,
        };
    }

    public render() {
        const { locationBreadcrumb } = this.props;

        const content = <LocationBreadcrumbs locationData={locationBreadcrumb} asString={false} />;
        const crumbTitle = LocationBreadcrumbs.renderString(locationBreadcrumb);

        return (
            <React.Fragment>
                <div className={classNames("pageLocation", this.props.className)}>
                    <span className="pageLocation-label" aria-hidden={true}>
                        {t("To: ")}
                    </span>
                    <Button
                        title={crumbTitle}
                        type="button"
                        aria-label={t("Article Location:")}
                        className="pageLocation"
                        onClick={this.showLocationChooser}
                    >
                        {content}
                    </Button>
                </div>
                {this.state.showLocationChooser && (
                    <LocationPicker {...this.props} data={this.props.children} exitHandler={this.hideLocationChooser} />
                )}
            </React.Fragment>
        );
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

export default withLocationPicker(PageLocation);
