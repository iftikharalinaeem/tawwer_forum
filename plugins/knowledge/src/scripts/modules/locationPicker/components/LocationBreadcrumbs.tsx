/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { t } from "@library/application";
import { IKbCategoryFragment } from "@knowledge/@types/api";

interface IProps {
    locationData: IKbCategoryFragment[];
    asString: boolean;
}

/**
 * Displays the current location of a location picker.
 */
export default class LocationBreadcrumbs extends React.Component<IProps> {
    /**
     * Render a string version of a breadcrumb.
     *
     * @param breadcrumbData - The category data to render.
     */
    public static renderString(breadcrumbData: IKbCategoryFragment[]): string {
        if (breadcrumbData.length === 0) {
            return t("Set Page Location");
        }

        const accessibleCrumbSeparator = `/`;
        const crumbCount = breadcrumbData.length - 1;
        let crumbTitle = t("Page Location: ") + accessibleCrumbSeparator;
        breadcrumbData.forEach((crumb, index) => {
            const lastElement = index === crumbCount;
            crumbTitle += crumb.name;
            if (!lastElement) {
                crumbTitle += accessibleCrumbSeparator;
            }
        });

        return crumbTitle;
    }

    /**
     * Render breadcrumbs as normal react components.
     */
    public render() {
        if (this.props.locationData.length === 0) {
            return t("Set Page Location");
        }
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
