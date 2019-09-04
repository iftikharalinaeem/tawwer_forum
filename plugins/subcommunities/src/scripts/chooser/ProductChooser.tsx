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

interface IProps {
    forLocale: string;
    communityID: number | null;
    onBack?: () => void;
    linkSuffix?: string;
    hideNoProductCommunities?: boolean;
}

export function ProductChooser(props: IProps) {
    const productsForLocale = useProductsForLocale(props.forLocale);
    const buttonRef = useRef<HTMLButtonElement>(null);

    useLayoutEffect(() => {
        buttonRef.current && buttonRef.current.focus();
    }, []);

    if (!productsForLocale) {
        return <Loader small padding={10} />;
    }

    const classes = chooserClasses();
    return (
        <div>
            {props.onBack && (
                <DropDownItemButton buttonRef={buttonRef} onClick={props.onBack}>
                    <span className={classes.rowBack}>
                        <LeftChevronCompactIcon className={classes.rowArrow} />
                        <LocaleDisplayer localeContent={props.forLocale} />
                    </span>
                </DropDownItemButton>
            )}
            <DropDownItemSeparator />
            {productsForLocale.map(productGroup => {
                const { product, community } = productGroup;
                return (
                    <DropDownItemLink
                        key={`product${product ? product.productID : ""}-sub${community.subcommunityID}`}
                        to={window.location.origin + assetUrl("/" + community.folder) + props.linkSuffix}
                    >
                        <span className={classes.rowIndented}>{community.name}</span>
                    </DropDownItemLink>
                );
            })}
        </div>
    );
}
