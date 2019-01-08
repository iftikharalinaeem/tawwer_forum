/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { t } from "@library/application";
import Message from "@knowledge/modules/common/Message";

interface IProps {
    onRestoreClick: () => void;
    isLoading: boolean;
}

/**
 * Notice in the event that an article has been deleted.
 */
export default class ArticleDeletedMessage extends React.Component<IProps> {
    public render() {
        const { onRestoreClick, isLoading } = this.props;
        return (
            <Message
                title={t("This article has been deleted.")}
                text={t("You can see this message because you have special permissions.")}
                actionHandler={onRestoreClick}
                isActionLoading={isLoading}
                actionTitle={t("Restore")}
            />
        );
    }
}
