/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardToggle } from "@dashboard/forms/DashboardToggle";
import { t } from "@library/utility/appUtils";
import { useProductsState } from "@subcommunities/products/productSelectors";
import React, { useState } from "react";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { useProductActions } from "@subcommunities/products/ProductActions";
import { LoadStatus } from "@library/@types/api/core";
import Translate from "@library/content/Translate";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { ProductManager } from "@subcommunities/products/ProductManager";

interface IProps {}

/**
 * Render out a form group with selection UI for this input.
 * Renders a hidden input for compatibity with Gdn_Form.
 */
export function ProductIntegrationFormGroup(props: IProps) {
    const { enableStatus, enabled } = useProductsState();
    const { toggleFeatureEnabled } = useProductActions();
    const [showModal, setShowModal] = useState(false);

    return (
        <>
            {showModal && <ProductManager onClose={() => setShowModal(false)} asModal />}
            <DashboardFormGroup
                labelType={DashboardLabelType.WIDE}
                label={t("Enable Product Integration")}
                description={
                    <Translate
                        source="When enabled, you can <0>manage products</0>, and group subcommunities by those products."
                        c0={text => {
                            if (enabled) {
                                return (
                                    <Button baseClass={ButtonTypes.TEXT_PRIMARY} onClick={() => setShowModal(true)}>
                                        {text}
                                    </Button>
                                );
                            } else {
                                return text;
                            }
                        }}
                    />
                }
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
        </>
    );
}
