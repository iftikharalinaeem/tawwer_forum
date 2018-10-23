/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import Translate from "@library/components/translation/Translate";
import ProfileLink from "@library/components/ProfileLink";
import DateTime from "@library/components/DateTime";
import { Link } from "react-router-dom";
import { IUserFragment } from "@dashboard/@types/api";

interface IProps {
    updateUser: IUserFragment;
    dateUpdated: string;
    permaLink: string;
}

export class ArticleMeta extends React.Component<IProps> {
    public render() {
        const { dateUpdated, updateUser, permaLink } = this.props;

        return (
            <React.Fragment>
                <span className="meta">
                    <Translate source="By <0/>" c0={<ProfileLink className="meta" username={updateUser.name} />} />
                </span>
                <span className="meta">
                    <Translate
                        source="Last Updated: <0/>"
                        c0={
                            <Link to={permaLink} className="meta">
                                <DateTime timestamp={dateUpdated} />
                            </Link>
                        }
                    />
                </span>
            </React.Fragment>
        );
    }
}
