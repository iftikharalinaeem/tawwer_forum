/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */
import React from "react";
import { SearchResultMeta } from "@knowledge/modules/common/SearchResultMeta";

export const dummyMetaData = (
    <SearchResultMeta
        dateUpdated="2018-10-22T16:56:37.423Z"
        updateUser={{ name: "Dummy Data User", photoUrl: "", userID: 1, dateLastActive: "2018-10-22T16:56:37.423Z" }}
    />
) as React.ReactNode;
