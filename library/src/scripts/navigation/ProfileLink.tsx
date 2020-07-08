/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { makeProfileUrl } from "../utility/appUtils";
import classNames from "classnames";
import { UserCardModuleLazyLoad } from "@library/features/users/modules/UserCardModuleLazyLoad";

interface IProps {
    username: string;
    userID: number;
    className?: string;
    children?: React.ReactNode;
    isUserCard?: boolean;
    cardAsModal?: boolean;
}

/**
 * Class representing a link to a users profile. This will do a full page refresh.
 */
export default function ProfileLink(props: IProps) {
    const { username, isUserCard = true, cardAsModal } = props;
    const children = props.children || username;

    if (isUserCard) {
        return (
            <UserCardModuleLazyLoad
                buttonContent={<span className={classNames(props.className)}>{children}</span>}
                openAsModal={cardAsModal}
                userID={props.userID}
            />
        );
    } else {
        return (
            <a href={makeProfileUrl(username)} className={classNames(props.className)}>
                {children}
            </a>
        );
    }
}
