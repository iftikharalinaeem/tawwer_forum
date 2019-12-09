/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Message from "@library/messages/Message";
import { t } from "@vanilla/i18n";
import { WarningIcon } from "@library/icons/common";
import { messagesClasses } from "@library/messages/messageStyles";
import Translate from "@library/content/Translate";
import DateTime from "@library/content/DateTime";
import classNames from "classnames";

interface IProps {
    articleID: number;
    date?: string;
}

export function ArticleUntranslatedMessage(props: IProps) {
    const classes = messagesClasses();

    const textNoDate = t(
        "This article was edited in its source locale. Edit this article to update its translation and clear this message.",
    );
    const textDate = props.date ? (
        <Translate
            source={
                "This article was edited in its source locale on <0/>. Edit this article to update its translation and clear this message."
            }
            c0={<DateTime timestamp={props.date} />}
        />
    ) : (
        textNoDate
    );

    return <Message stringContents={textNoDate} icon={<WarningIcon />} contents={textDate} />;
}
