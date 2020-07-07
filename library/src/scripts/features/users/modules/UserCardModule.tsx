/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { useUser } from "@library/features/users/userHooks";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import ErrorMessages from "@library/forms/ErrorMessages";
import { notEmpty } from "@vanilla/utils";
import PopupUserCard, { IUserCardInfo } from "@library/features/users/ui/PopupUserCard";

export interface IUserCardModule {
    userID: number;
    children?: React.ReactNode; // fallback to original HTML
}

export function UserCardModule(props: IUserCardModule) {
    const { userID, children } = props;
    const user = useUser({ userID });

    // Fallback to the original link, unchanged
    if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(user.status) && !user.data) {
        return <>{children}</>;
    }

    if (!user.data || user.error) {
        return (
            <>
                {/* Fallback to the original link, unchanged */}
                {children}
                <ErrorMessages errors={[user.error].filter(notEmpty)} />
            </>
        );
    }

    const userCardInfo: IUserCardInfo = {
        email: user.data.email,
        userID: user.data.userID,
        name: user.data.name,
        photoUrl: user.data.photoUrl,
        dateLastActive: user.data.dateLastActive || undefined,
        dateJoined: user.data.dateInserted,
        label: user.data.label,
        countDiscussions: user.data.countDiscussions || 0,
        countComments: user.data.countComments || 0,
    };

    return <PopupUserCard user={userCardInfo} />;
}
