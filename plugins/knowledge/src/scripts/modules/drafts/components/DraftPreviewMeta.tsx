/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Translate from "@library/components/translation/Translate";
import DateTime from "@library/components/DateTime";
import { IKbCategoryFragment } from "@knowledge/@types/api/kbCategory";
import LocationBreadcrumbs from "@knowledge/modules/locationPicker/components/LocationBreadcrumbs";
import classNames from "classnames";

interface IProps {
    dateUpdated: string;
    location: IKbCategoryFragment[];
    className?: string;
}

/*
 * Implements draft preview meta data
 */
export class DraftPreviewMeta extends React.Component<IProps> {
    public render() {
        const { dateUpdated, location } = this.props;
        return (
            <div className={classNames("metas", "draftPreview-metas", this.props.className)}>
                {dateUpdated && (
                    <span className="meta">
                        <Translate source="Last Updated: <0/>" c0={<DateTime timestamp={dateUpdated} />} />
                    </span>
                )}
                {location && (
                    <span className="meta">
                        <LocationBreadcrumbs locationData={location} />
                    </span>
                )}
            </div>
        );
    }
}
