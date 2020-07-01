/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import PopupUserCard from "@library/features/search/PopupUserCard";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";

export default {
    component: PopupUserCard,
    title: "PopupUserCard",
};

const m = {
    userInfo: {
        userID: 1,
        name: "Valérie Robitaille",
        photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/164/nQQG7FTJACOTX.jpg",
        dateLastActive: "May 24th",
        dateJoined: "May 2017",
        label: "Product Manager",
    },
    links: {
        profileLink: "www.google.com",
        messageLink: "www.google.com",
    },
    stats: {
        discussions: 20,
        comments: 1375,
    },
};

export const UserCardNoState = () => <PopupUserCard {...m} />;

export const UserCardWithoutPermission = storyWithConfig(
    {
        storeState: {
            users: {
                permissions: {
                    status: LoadStatus.SUCCESS,
                    data: {
                        isAdmin: false,
                        permissions: [
                            {
                                type: "global",
                                id: 1,
                                permissions: {
                                    "email.view": false,
                                },
                            },
                        ],
                    },
                },
            },
        },
    },
    () => <PopupUserCard {...m} />,
);

export const UserCardWithPermission = storyWithConfig(
    {
        storeState: {
            users: {
                permissions: {
                    status: LoadStatus.SUCCESS,
                    data: {
                        isAdmin: false,
                        permissions: [
                            {
                                type: "global",
                                id: 1,
                                permissions: {
                                    "email.view": true,
                                },
                            },
                        ],
                    },
                },
            },
        },
    },
    () => <PopupUserCard {...m} />,
);
