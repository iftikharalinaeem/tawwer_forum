/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { DashboardMediaItem } from "@dashboard/tables/DashboardMediaItem";
import { DashboardTable } from "@dashboard/tables/DashboardTable";
import { DashboardTableOptions } from "@dashboard/tables/DashboardTableOptions";
import { IKnowledgeBase, KnowledgeBaseStatus } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { AlertIcon, DeleteIcon, EditIcon } from "@library/icons/common";
import LinkAsButton from "@library/routing/LinkAsButton";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import { getMeta } from "@library/utility/appUtils";
import { t } from "@vanilla/i18n";
import React, { useState } from "react";
import Button from "@library/forms/Button";
import ModalConfirm from "@library/modal/ModalConfirm";

interface IProps {}
export function KnowledgeBaseAddEdit(props: IProps) {
    const [open, setOpen] = useState(false);

    return (
        <>
            <Button baseClass={ButtonTypes.ICON_COMPACT} onClick={() => setOpen(true)}>
                <EditIcon />
            </Button>
            {open && <ModalConfirm />}
        </>
    );
}
