/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import { CheckIcon, LeftChevronCompactIcon } from "@library/icons/common";
import Loader from "@library/loaders/Loader";
import { siteUrl } from "@library/utility/appUtils";
import { useProductsForLocale } from "@subcommunities/products/productSelectors";
import React, { useRef, useLayoutEffect } from "react";
import { subcommunityChooserClasses } from "@subcommunities/chooser/subcommunityChooserStyles";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import { LocaleDisplayer, useLocaleInfo } from "@vanilla/i18n";
import { useCommunityFilterContext } from "@subcommunities/CommunityFilterContext";
import { useAvailableSubcommunityLocales } from "@subcommunities/subcommunities/subcommunitySelectors";

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
    const productsForLocale = useProductsForLocale(props.forLocale);
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
                        to={siteUrl("/" + community.folder + options.linkSuffix)}
                    >
                        <span className={classes.rowIndented}>
                            {community.name}
                            {community.subcommunityID === props.activeSubcommunityID && (
                                <CheckIcon className={classes.check} />
                            )}
                        </span>
                    </DropDownItemLink>
                );
            })}
        </div>
    );
}
