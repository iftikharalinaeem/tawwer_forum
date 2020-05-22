/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { subcommunityChooserVariables } from "@subcommunities/chooser/subcommunityChooserStyles";
import DropDownItemSeparator from "@vanilla/library/src/scripts/flyouts/items/DropDownItemSeparator";
import { SubcommunityChooserDropdown } from "@subcommunities/chooser/SubcommunityChooserDropdown";
import { ButtonTypes } from "@vanilla/library/src/scripts/forms/buttonTypes";
import { dropDownClasses } from "@vanilla/library/src/scripts/flyouts/dropDownStyles";
import { GlobeIcon } from "@vanilla/library/src/scripts/icons/common";
import { ProductIcon } from "@subcommunities/products/ProductIcon";
import {
    useSubcommunities,
    useAvailableSubcommunityLocales,
} from "@subcommunities/subcommunities/subcommunitySelectors";

export function SubcommunityChooserHamburgerGroup() {
    const availableLocales = useAvailableSubcommunityLocales();
    const { subcommunitiesByProductID } = useSubcommunities();

    if (!subcommunityChooserVariables().options.enabled || !availableLocales || !subcommunitiesByProductID.data) {
        return null;
    }

    const hasMultipleLocales = Object.values(availableLocales).length > 1;
    const hasMultipleProducts = Object.keys(subcommunitiesByProductID.data).length > 1;

    return (
        <>
            <DropDownItemSeparator />
            {hasMultipleLocales && (
                <SubcommunityChooserDropdown
                    defaultSection="locale"
                    buttonType={ButtonTypes.CUSTOM}
                    buttonClass={dropDownClasses().action}
                    fullWidthIcon={<GlobeIcon className={dropDownClasses().actionIcon} />}
                    fullWidth
                />
            )}
            {hasMultipleProducts && (
                <SubcommunityChooserDropdown
                    defaultSection={"product"}
                    buttonType={ButtonTypes.CUSTOM}
                    buttonClass={dropDownClasses().action}
                    fullWidthIcon={<ProductIcon className={dropDownClasses().actionIcon} />}
                    fullWidth
                />
            )}
        </>
    );
}
