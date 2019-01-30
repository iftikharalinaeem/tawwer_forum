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
import classNames from "classnames";

interface IProps {
    updateUser: IUserFragment;
    dateUpdated: string;
    crumbs?: ICrumbString[];
    status?: ArticleStatus;
    type?: string;
}

export class SearchResultMeta extends React.Component<IProps> {
    public render() {
        const { dateUpdated, updateUser, crumbs, status, type } = this.props;
        const isDeleted = status === ArticleStatus.DELETED;
        return (
            <React.Fragment>
                {updateUser &&
                    updateUser.name && (
                        <span className={classNames("meta")}>
                            {isDeleted ? (
                                <span className={classNames("meta-inline", "isDeleted")}>
                                    <Translate source="Deleted <0/>" c0={type} />
                                </span>
                            ) : (
                                <Translate
                                    source="<0/> by <1/>"
                                    c0={type ? t(capitalizeFirstLetter(type)) : undefined}
                                    c1={updateUser.name}
                                />
                            )}
                        </span>
                    )}

                <span className="meta">
                    <Translate source="Last Updated: <0/>" c0={<DateTime timestamp={dateUpdated} />} />
                </span>
                {crumbs && crumbs.length > 0 && <BreadCrumbString className="meta" crumbs={crumbs} />}
            </React.Fragment>
        );
    }
}
