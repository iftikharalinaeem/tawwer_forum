/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { addComponent } from "@library/utility/componentRegistry";
import { ProductIntegrationFormGroup } from "@subcommunities/forms/ProductIntegrationFormGroup";
import { ProductSelectorFormGroup } from "@subcommunities/forms/ProductSelectorFormGroup";
import { SubcommunityChooser } from "@subcommunities/chooser/SubcommunityChooser";

addComponent("subcommunity-chooser", SubcommunityChooser);
addComponent("product-integration-form-group", ProductIntegrationFormGroup);
addComponent("product-selector-form-group", ProductSelectorFormGroup);
