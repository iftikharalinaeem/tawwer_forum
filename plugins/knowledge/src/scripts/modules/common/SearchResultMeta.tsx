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
import { ArticleStatus } from "@knowledge/@types/api";
import { capitalizeFirstLetter } from "@library/utility";

interface IProps {
    updateUser: IUserFragment;
    dateUpdated: string;
    crumbs?: ICrumbString[];
    status: ArticleStatus;
    type: string;
}

export class SearchResultMeta extends React.Component<IProps> {
    public render() {
        const { dateUpdated, updateUser, crumbs, status, type } = this.props;
        const deleted = status === ArticleStatus.DELETED;
        const user = updateUser;

        console.log("this.props: ", this.props);

        const resultType = deleted ? (
            <span className="meta-inline isDeleted">{t("Deleted")}</span>
        ) : (
            capitalizeFirstLetter(type)
        );
        return (
            <React.Fragment>
                <span className="meta">
                    <Translate source="<0/> by <1/>" c0={resultType} c1={user.name} />
                </span>
                <span className="meta">
                    <Translate source="Last Updated: <0/>" c0={<DateTime timestamp={dateUpdated} />} />
                </span>
                {crumbs && crumbs.length > 0 && <BreadCrumbString className="meta" crumbs={crumbs} />}
            </React.Fragment>
        );
    }
}
