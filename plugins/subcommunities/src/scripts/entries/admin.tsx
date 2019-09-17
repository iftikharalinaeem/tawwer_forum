/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { addComponent } from "@library/utility/componentRegistry";
import { ProductIntegrationFormGroup } from "@subcommunities/forms/ProductIntegrationFormGroup";
import { ProductSelectorFormGroup } from "@subcommunities/forms/ProductSelectorFormGroup";

addComponent("product-integration-form-group", ProductIntegrationFormGroup);
addComponent("product-selector-form-group", props => (
    <ProductSelectorFormGroup {...props} formFieldName="ProductID" valueType="productID" />
));
addComponent("site-section-group-selector-form-group", props => (
    <ProductSelectorFormGroup {...props} formFieldName="siteSectionGroup" valueType="sectionGroup" />
));
