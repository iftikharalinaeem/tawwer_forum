/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState } from "react";
import ModalConfirm from "@library/modal/ModalConfirm";
import { t } from "@library/utility/appUtils";
import Translate from "@library/content/Translate";
import { makeEditorUrl } from "@knowledge/routes/makeEditorUrl";

interface IProps {
    discussionID: number;
}

export function ConvertDiscussionModal(props: IProps) {
    const [isVisible, setIsVisible] = useState(true);
    const navigateToDraft = () => {
        const url = makeEditorUrl({ discussionID: props.discussionID });
        window.location.href = url;
    };

    return (
        <ModalConfirm
            isVisible={isVisible}
            onCancel={() => setIsVisible(false)}
            title={t("Convert to Article")}
            onConfirm={navigateToDraft}
            confirmTitle={t("Create Article")}
        >
            <Translate source="This will create an article that is a copy of this discussion. The discussion will then be linked to the new article." />
        </ModalConfirm>
    );
}
