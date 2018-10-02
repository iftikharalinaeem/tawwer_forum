/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/application";
import { IKbCategoryFragment } from "@knowledge/@types/api";

interface IProps {
    locationData: IKbCategoryFragment[];
    asString: boolean;
}

/**
 * This component allows to display and edit the location of the current page.
 * Calls the LocationChooser component when clicked.
 */
export default class LocationBreadcrumbs extends React.Component<IProps> {
    public static renderString(data: IKbCategoryFragment[]): string {
        if (data.length === 0) {
            return t("Set Page Location");
        }

        const accessibleCrumbSeparator = `/`;
        const crumbCount = data.length - 1;
        let crumbTitle = t("Page Location: ") + accessibleCrumbSeparator;
        data.forEach((crumb, index) => {
            const lastElement = index === crumbCount;
            crumbTitle += crumb.name;
            if (!lastElement) {
                crumbTitle += accessibleCrumbSeparator;
            }
        });

        return crumbTitle;
    }

    public render() {
        if (this.props.locationData.length === 0) {
            return t("Set Page Location");
        }
        return this.renderHTML();
    }

    private renderHTML() {
        const { locationData } = this.props;
        const accessibleCrumbSeparator = `/`;
        const crumbCount = locationData.length - 1;
        const crumbs = locationData.map((crumb, index) => {
            const lastElement = index === crumbCount;
            const crumbSeparator = `›`;
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

        return <span className="breadcrumbs">{crumbs}</span>;
    }
}
