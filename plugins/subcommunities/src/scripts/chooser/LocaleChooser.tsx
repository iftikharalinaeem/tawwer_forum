/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { RightChevronIcon } from "@library/icons/common";
import Loader from "@library/loaders/Loader";
import { useProductsByLocale } from "@subcommunities/products/productSelectors";
import React from "react";
import { chooserClasses } from "@subcommunities/chooser/chooserStyles";

interface IProps {
    value: string | null;
    onChange: (value: string) => void;
}

export function LocaleChooser(props: IProps) {
    const productsByLocale = useProductsByLocale();

    if (productsByLocale === null) {
        return <Loader />;
    }

    const classes = chooserClasses();

    return (
        <div>
            {Object.entries(productsByLocale).map(([localeKey, products]) => {
                return (
                    <DropDownItemButton key={localeKey} onClick={() => props.onChange(localeKey)}>
                        <span className={classes.row}>
                            {localeKey}
                            <RightChevronIcon className={classes.rowArrow} />
                        </span>
                    </DropDownItemButton>
                );
            })}
        </div>
    );
}
