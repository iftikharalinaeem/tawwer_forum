/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import Translate from "@library/components/translation/Translate";
import DateTime from "@library/components/DateTime";
import { IUserFragment } from "@library/@types/api";
import BreadCrumbString, { ICrumbString } from "@library/components/BreadCrumbString";
import { t } from "@library/application";

interface IProps {
    updateUser: IUserFragment;
    dateUpdated: string;
    crumbs?: ICrumbString[];
    deleted?: boolean;
}

export class SearchResultMeta extends React.Component<IProps> {
    public static defaultProps = {
        deleted: false,
    };
    public render() {
        const { dateUpdated, updateUser, crumbs, deleted } = this.props;

        return (
            <React.Fragment>
                <span className="meta">
                    {deleted ? (
                        <>
                            <span className="meta-inline isDeleted">{t("Deleted")}</span>
                            <Translate source=" by <0/>" c0={updateUser.name} />
                        </>
                    ) : (
                        <Translate source="By <0/>" c0={updateUser.name} />
                    )}
                </span>
                <span className="meta">
                    <Translate source="Last Updated: <0/>" c0={<DateTime timestamp={dateUpdated} />} />
                </span>
                {crumbs && crumbs.length > 0 && <BreadCrumbString className="meta" crumbs={crumbs} />}
            </React.Fragment>
        );
    }
}
