/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import Translate from "@library/content/Translate";
import ProfileLink from "@library/navigation/ProfileLink";
import DateTime from "@library/content/DateTime";
import { IUserFragment } from "@library/@types/api/users";
import { metasClasses } from "@library/styles/metasStyles";
import SmartLink from "@library/routing/links/SmartLink";
import { UserCardModuleLazyLoad } from "@vanilla/library/src/scripts/features/users/modules/UserCardModuleLazyLoad";

interface IProps {
    updateUser: IUserFragment;
    dateUpdated: string;
    permaLink: string;
    featured?: boolean;
}

export class ArticleMeta extends React.Component<IProps> {
    public render() {
        const { dateUpdated, updateUser, permaLink, featured } = this.props;
        const classesMetas = metasClasses();

        return (
            <React.Fragment>
                <span className={classesMetas.meta}>
                    <Translate
                        source="By <0/>"
                        c0={
                            <ProfileLink className={classesMetas.meta} username={updateUser.name}>
                                <UserCardModuleLazyLoad userID={updateUser.userID} buttonContent={updateUser.name} />
                            </ProfileLink>
                        }
                    />
                </span>
                <span className={classesMetas.meta}>
                    <Translate
                        source="Last Updated: <0/>"
                        c0={
                            <SmartLink to={permaLink} className={classesMetas.meta}>
                                <DateTime timestamp={dateUpdated} />
                            </SmartLink>
                        }
                    />
                </span>
                {featured ? (
                    <span className={classesMetas.meta}>
                        <Translate source={"Featured Article"} />
                    </span>
                ) : (
                    <></>
                )}
            </React.Fragment>
        );
    }
}
