import { DashboardTable } from "@dashboard/tables/DashboardTable";
import { TableColumnSize } from "@dashboard/tables/DashboardTableHeadItem";
import React, { useState } from "react";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { t } from "@vanilla/i18n";
import { BrowserRouter } from "react-router-dom";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
const { HeadItem } = DashboardTable;

export function ManageWebhooksPage() {
    return (
        <BrowserRouter>
            <DashboardHeaderBlock
                title={t("Webhooks")}
                // showBackLink={status === KnowledgeBaseStatus.DELETED}
                // actionButtons={
                //     status === KnowledgeBaseStatus.PUBLISHED && (
                //         <Button
                //             buttonRef={toggleButtonRef}
                //             baseClass={ButtonTypes.DASHBOARD_PRIMARY}
                //             onClick={() => setIsFormOpen(true)}
                //         >
                //             {t("Add Webhook")}
                //         </Button>
                //     )
                // }
            />
        </BrowserRouter>
    );
}
