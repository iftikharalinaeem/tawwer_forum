/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { addComponent } from "@library/utility/componentRegistry";
import { ProductIntegrationFormGroup } from "@subcommunities/forms/ProductIntegrationFormGroup";
import { ProductSelectorFormGroup } from "@subcommunities/forms/ProductSelectorFormGroup";
import { getMeta } from "@library/utility/appUtils";
import { SubcommunityThemeFormGroup } from "@subcommunities/forms/SubcommunityThemeFormGroup";

const isProductIntgrationEnabled = getMeta("featureFlags.SubcommunityProducts.Enabled") === true;

addComponent("product-integration-form-group", ProductIntegrationFormGroup, { overwrite: true });

if (isProductIntgrationEnabled) {
    addComponent(
        "product-selector-form-group",
        props => <ProductSelectorFormGroup {...props} formFieldName="ProductID" valueType="productID" />,
        { overwrite: true },
    );
    addComponent(
        "site-section-group-selector-form-group",
        props => <ProductSelectorFormGroup {...props} formFieldName="siteSectionGroup" valueType="sectionGroup" />,
        { overwrite: true },
    );
    addComponent("ProductSelectorFormGroup", ProductSelectorFormGroup);
    addComponent(
        "subcommunity-theme-form-group",
        props => <SubcommunityThemeFormGroup {...props} formFieldName="themeID" />,
        { overwrite: true },
    );
}
