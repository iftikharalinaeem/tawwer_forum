/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Message from "@library/messages/Message";
import { EditorRoute } from "@knowledge/routes/pageRoutes";
import { t } from "@vanilla/i18n";
import { useHistory } from "react-router";
import { getRelativeUrl } from "@library/utility/appUtils";

interface IProps {
    articleID: number;
}

export function ArticleUntranslatedMessage(props: IProps) {
    const history = useHistory();

    const onConfirm = () => {
        let url = EditorRoute.url({ articleID: props.articleID });
        url = getRelativeUrl(url);
        history.push(url);
    };

    return (
        <Message
            confirmText={t("Translate")}
            onConfirm={onConfirm}
            stringContents={t("This article hasn't been translated yet.")}
        />
    );
}
