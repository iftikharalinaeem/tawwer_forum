/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useSelector } from "react-redux";
import { IGetUserByIDQuery, useUserActions } from "@library/features/users/UserActions";
import { IUsersStoreState } from "@library/features/users/userModel";
import { useEffect } from "react";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";

export function useUser(query: IGetUserByIDQuery) {
    const actions = useUserActions();

    const existingResult = useSelector((state: IUsersStoreState) => {
        return state.users.user;
    });

    const { status } = existingResult;

    useEffect(() => {
        if (LoadStatus.PENDING.includes(status)) {
            actions.getUserByID(query);
        }
    }, [status, actions, query]);

    return existingResult;
}
