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
}

export class SearchResultMeta extends React.Component<IProps> {
    public render() {
        const { dateUpdated, updateUser } = this.props;

        return (
            <React.Fragment>
                <span className="metaStyle">
                    <Translate source="By <0/>" c0={updateUser.name} />
                </span>
                <span className="metaStyle">
                    <Translate source="Last Updated: <0/>" c0={<DateTime timestamp={dateUpdated} />} />
                </span>
            </React.Fragment>
        );
    }
}
