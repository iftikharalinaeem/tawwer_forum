/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import ModalConfirm from "@library/modal/ModalConfirm";
import { t } from "@library/utility/appUtils";
import Translate from "@library/content/Translate";
import { EditorRoute } from "@knowledge/routes/pageRoutes";

interface IProps {
    discussionID: number;
}

export function ConvertDiscussionModal(props: IProps) {
    const navigateToDraft = () => {
        const url = EditorRoute.url({ discussionID: props.discussionID });
        window.location.href = url;
    };

    return (
        <ModalConfirm
            title={t("Convert to Article")}
            onConfirm={navigateToDraft}
            elementToFocusOnExit={document.body}
            confirmTitle={t("Create Article")}
        >
            <Translate source="This will create an article that is a copy of this discussion. The discussion will then be linked to the new article." />
        </ModalConfirm>
    );
}
