/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import DropDownItem from "@library/flyouts/items/DropDownItem";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import { LeftChevronCompactIcon } from "@library/icons/common";
import Loader from "@library/loaders/Loader";
import { assetUrl } from "@library/utility/appUtils";
import { useProductsForLocale } from "@subcommunities/products/productSelectors";
import React, { useRef, useLayoutEffect } from "react";
import { chooserClasses } from "@subcommunities/chooser/chooserStyles";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import { LocaleDisplayer } from "@subcommunities/chooser/LocaleDisplayer";
import { useCommunityFilterContext } from "@subcommunities/CommunityFilterContext";
import { useLocaleInfo } from "@subcommunities/subcommunities/subcommunitySelectors";

interface IProps {
    forLocale: string;
    communityID: number | null;
    onBack?: () => void;
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
    const localeInfo = useLocaleInfo();

    // Focus the button when the page opens.
    useLayoutEffect(() => {
        backButtonRef.current && backButtonRef.current.focus();
    }, []);

    if (!productsForLocale || !localeInfo) {
        return <Loader small padding={10} />;
    }

    const classes = chooserClasses();
    return (
        <div>
            {props.onBack && localeInfo.count > 1 && (
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
                        to={window.location.origin + assetUrl("/" + community.folder) + options.linkSuffix}
                    >
                        <span className={classes.rowIndented}>{community.name}</span>
                    </DropDownItemLink>
                );
            })}
        </div>
    );
}
