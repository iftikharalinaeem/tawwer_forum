/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { DropDownItemMetas, DropDownItemMeta } from "@library/components/dropdown";
import Translate from "@library/components/translation/Translate";
import DateTime from "@library/components/DateTime";
import ProfileLink from "@library/components/navigation/ProfileLink";
import { IUserFragment } from "@library/@types/api";

interface IProps {
    dateInserted: string;
    insertUser: IUserFragment;
    dateUpdated: string;
    updateUser: IUserFragment;
}

export default function InsertUpdateMetas(props: IProps) {
    return (
        <DropDownItemMetas>
            <DropDownItemMeta>
                <Translate
                    source="Published on <0/> by <1/>"
                    c0={<DateTime timestamp={props.dateInserted} />}
                    c1={<ProfileLink className="metaStyle" username={props.insertUser.name} />}
                />
            </DropDownItemMeta>
            <DropDownItemMeta>
                <Translate
                    source="Updated on <0/> by <1/>"
                    c0={<DateTime timestamp={props.dateUpdated} />}
                    c1={<ProfileLink className="metaStyle" username={props.updateUser.name} />}
                />
            </DropDownItemMeta>
        </DropDownItemMetas>
    );
}
