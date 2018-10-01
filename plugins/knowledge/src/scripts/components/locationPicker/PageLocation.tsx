/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/application";
import classNames from "classnames";
import { IBreadcrumbsProps } from "../Breadcrumbs";
import { getRequiredID } from "@library/componentIDs";
import LocationPicker from "@knowledge/components/locationPicker/LocationPicker";
import Button from "@dashboard/components/forms/Button";

interface IProps extends IBreadcrumbsProps {
    displayType?: string;
    isSection?: boolean;
    url?: string;
    parentID?: number;
    recordID?: number;
    recordType?: string;
}

interface IState {
    showLocationChooser: boolean;
}

/**
 * This component allows to display and edit the location of the current page.
 * Calls the LocationChooser component when clicked.
 */
export default class PageLocation extends React.Component<IProps, IState> {
    public constructor(props) {
        super(props);
        this.state = {
            showLocationChooser: false,
        };
    }

    public render() {
        const accessibleCrumbSeparator = `/`;
        let content;
        let crumbTitle;

        if (this.props.children && this.props.children.length > 0) {
            const crumbCount = this.props.children.length - 1;
            crumbTitle = t("Page Location: ") + accessibleCrumbSeparator;
            const crumbs = this.props.children.map((crumb, index) => {
                const lastElement = index === crumbCount;
                const crumbSeparator = `›`;
                crumbTitle += crumb.name;
                if (!lastElement) {
                    crumbTitle += accessibleCrumbSeparator;
                }
                return (
                    <React.Fragment key={`locationBreadcrumb-${index}`}>
                        <span className="breadcrumb-link">{crumb.name}</span>
                        {!lastElement && (
                            <span className="breadcrumb-item breadcrumbs-separator">
                                <span aria-hidden={true} className="breadcrumbs-separatorIcon">
                                    {crumbSeparator}
                                </span>
                                <span className="sr-only">{accessibleCrumbSeparator}</span>
                            </span>
                        )}
                    </React.Fragment>
                );
            });

            content = <span className="breadcrumbs">{crumbs}</span>;
        } else {
            content = t("Set Page Location");
            crumbTitle = content;
        }

        return (
            <React.Fragment>
                <div className={classNames("pageLocation", this.props.className)}>
                    <span className="pageLocation-label" aria-hidden={true}>
                        {t("To:")}
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
                {/*{this.state.showLocationChooser && */}
                <LocationPicker {...this.props} dismissFunction={this.hideLocationChooser} />
            </React.Fragment>
        );
    }

    private showLocationChooser = () => {
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
