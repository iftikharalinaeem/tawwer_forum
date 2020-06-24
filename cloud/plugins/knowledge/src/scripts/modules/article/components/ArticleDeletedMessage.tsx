/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { t } from "@library/utility/appUtils";
import Message from "@library/messages/Message";

interface IProps {
    onRestoreClick: () => void;
    isLoading: boolean;
}

/**
 * Notice in the event that an article has been deleted.
 */
export default class ArticleDeletedMessage extends React.Component<IProps> {
    public render() {
        const { onRestoreClick, isLoading = false } = this.props;
        return (
            <Message
                stringContents={t("This article has been deleted.")}
                title={t("This article has been deleted.")}
                contents={t("You can see this message because you have special permissions.")}
                onConfirm={onRestoreClick}
                isActionLoading={isLoading}
                confirmText={t("Restore")}
                icon={false}
            />
        );
    }
}
