/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { subcommunityChooserClasses } from "@subcommunities/chooser/subcommunityChooserStyles";
import { useCommunityFilterContext } from "@subcommunities/CommunityFilterContext";
import { useProductsForLocale } from "@subcommunities/products/productSelectors";
import { useAvailableSubcommunityLocales } from "@subcommunities/subcommunities/subcommunitySelectors";
import { getCurrentLocale, LocaleDisplayer } from "@vanilla/i18n";
import DropDownItemButton from "@vanilla/library/src/scripts/flyouts/items/DropDownItemButton";
import DropDownItemLink from "@vanilla/library/src/scripts/flyouts/items/DropDownItemLink";
import DropDownItemSeparator from "@vanilla/library/src/scripts/flyouts/items/DropDownItemSeparator";
import { LeftChevronCompactIcon } from "@vanilla/library/src/scripts/icons/common";
import Loader from "@vanilla/library/src/scripts/loaders/Loader";
import React, { useLayoutEffect, useRef } from "react";

interface IProps {
    forLocale: string;
    communityID: number | null;
    onBack?: () => void;
    activeSubcommunityID?: number;
}

/**
 * Choose a product or a subcommunity.
 * Behaviour of this component varies significantly based on the `CommunityFilterContext`.
 * That can enable a product requirement.
 */
export function ProductChooser(props: IProps) {
    const options = useCommunityFilterContext();
    const locale = props.forLocale ?? getCurrentLocale();
    const productsForLocale = useProductsForLocale(locale);
    const backButtonRef = useRef<HTMLButtonElement>(null);
    const availableLocales = useAvailableSubcommunityLocales();

    // Focus the button when the page opens.
    useLayoutEffect(() => {
        backButtonRef.current && backButtonRef.current.focus();
    }, []);

    if (!productsForLocale || !availableLocales) {
        return <Loader small padding={10} />;
    }

    const classes = subcommunityChooserClasses();
    return (
        <div>
            {props.onBack && Object.values(availableLocales).length > 1 && (
                <>
                    <DropDownItemButton buttonRef={backButtonRef} onClick={props.onBack}>
                        <span className={classes.rowBack}>
                            <LeftChevronCompactIcon className={classes.rowArrow} />
                            <LocaleDisplayer localeContent={props.forLocale} />
                        </span>
                    </DropDownItemButton>
                    <DropDownItemSeparator />
                </>
            )}
            {productsForLocale.map(productGroup => {
                const { product, community } = productGroup;
                return (
                    <DropDownItemLink
                        key={`product${product ? product.productID : ""}-sub${community.subcommunityID}`}
                        to={community.url}
                        isChecked={community.subcommunityID === props.activeSubcommunityID}
                    >
                        <span className={props.onBack ? classes.rowIndented : classes.row}>{community.name}</span>
                    </DropDownItemLink>
                );
            })}
        </div>
    );
}
