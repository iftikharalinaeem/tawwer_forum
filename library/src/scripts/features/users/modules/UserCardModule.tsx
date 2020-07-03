/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { useUser } from "@library/features/users/userHooks";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import Loader from "@library/loaders/Loader";
import ErrorMessages from "@library/forms/ErrorMessages";
import { notEmpty } from "@vanilla/utils";
import PopupUserCard from "@library/features/users/ui/PopupUserCard";

interface IProps {
    userID: number;
}

export function UserCardModule(props: IProps) {
    const { userID } = props;
    const user = useUser({ userID });

    if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(user.status) && !user.data) {
        return <Loader />;
    }

    if (!user.data || user.error) {
        return <ErrorMessages errors={[user.error].filter(notEmpty)} />;
    }

    return <PopupUserCard user={user.data} />;
}
