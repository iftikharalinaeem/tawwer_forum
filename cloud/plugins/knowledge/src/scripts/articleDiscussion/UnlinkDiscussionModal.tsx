/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState } from "react";
import ModalConfirm from "@library/modal/ModalConfirm";
import { t } from "@library/utility/appUtils";
import Translate from "@library/content/Translate";
import { LoadStatus } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";

interface IProps {
    discussionID: number;
}

export function UnlinkDiscussionModal(props: IProps) {
    const { discussionID } = props;
    const [unlinkStatus, setUnlinkStatus] = useState(LoadStatus.PENDING);
    const [isVisible, setIsVisible] = useState(true);

    const handleConfirm = async () => {
        setUnlinkStatus(LoadStatus.LOADING);
        await apiv2.delete(`/discussions/${discussionID}/canonical-url`);
        setUnlinkStatus(LoadStatus.SUCCESS);
        setIsVisible(false);
        // Refresh the page.
        window.location.href = window.location.href;
    };

    return (
        <ModalConfirm
            isVisible={isVisible}
            onCancel={() => setIsVisible(false)}
            title={t("Remove Article Link")}
            onConfirm={handleConfirm}
            isConfirmLoading={unlinkStatus === LoadStatus.LOADING}
            confirmTitle={t("Remove Link")}
        >
            <Translate source="This will remove the link to the article, but will not remove the article itself." />
        </ModalConfirm>
    );
}
