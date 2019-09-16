/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardToggle } from "@dashboard/forms/DashboardToggle";
import { t } from "@library/utility/appUtils";
import { useProductsState } from "@subcommunities/products/productSelectors";
import React from "react";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { useProductActions } from "@subcommunities/products/ProductActions";
import { LoadStatus } from "@library/@types/api/core";

interface IProps {}

/**
 * Render out a form group with selection UI for this input.
 * Renders a hidden input for compatibity with Gdn_Form.
 */
export function ProductIntegrationFormGroup(props: IProps) {
    const { enableStatus, enabled } = useProductsState();
    const { toggleFeatureEnabled } = useProductActions();

    return (
        <DashboardFormGroup
            labelType={DashboardLabelType.WIDE}
            label={t("Enable Product Integration")}
            description={t("When enabled, you can manage products, and group subcommunities by those products")}
        >
            <DashboardToggle
                inProgress={enableStatus === LoadStatus.LOADING}
                checked={enabled}
                onChange={async () => {
                    await toggleFeatureEnabled({ enabled: !enabled });

                    // Refresh the page.
                    // This is necessary because the table is currently rendered server side.
                    // As a result the only way to re-render this table is to do refresh the page.
                    window.location.href = window.location.href;
                }}
            />
        </DashboardFormGroup>
    );
}
