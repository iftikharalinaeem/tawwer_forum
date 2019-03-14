/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Translate from "@library/content/Translate";
import DateTime from "@library/content/DateTime";
import { IKbCategoryFragment } from "@knowledge/@types/api/kbCategory";
import classNames from "classnames";
import { metasClasses } from "@library/styles/metasStyles";

interface IProps {
    dateUpdated: string;
    className?: string;
}

/*
 * Implements draft preview meta data
 */
export class DraftPreviewMeta extends React.Component<IProps> {
    public render() {
        const { dateUpdated } = this.props;
        const classesMetas = metasClasses();
        return (
            <div className={classNames("metas", "draftPreview-metas", this.props.className)}>
                {dateUpdated && (
                    <span className={classesMetas.meta}>
                        <Translate source="Last Updated: <0/>" c0={<DateTime timestamp={dateUpdated} />} />
                    </span>
                )}
            </div>
        );
    }
}
