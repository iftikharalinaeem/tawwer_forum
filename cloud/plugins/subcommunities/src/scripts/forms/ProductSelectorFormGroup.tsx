/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardSelect } from "@dashboard/forms/DashboardSelect";
import { LoadStatus, IFieldError } from "@library/@types/api/core";
import Translate from "@library/content/Translate";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { t } from "@library/utility/appUtils";
import { ProductManager } from "@subcommunities/products/ProductManager";
import { useProducts } from "@subcommunities/products/productSelectors";
import React, { useMemo, useState } from "react";
import { makeSiteSectionGroup } from "@subcommunities/products/productTypes";
import { ILoadedProduct } from "@subcommunities/products/productReducer";
import { useSubcommunities } from "@subcommunities/subcommunities/subcommunitySelectors";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { noSubcommunitiesFieldError } from "@subcommunities/subcommunities/subcommunityErrors";

interface IProps {
    // Gdn_Form version (uncontrolled).
    initialValue: number | null | boolean; // Gdn_Form can give us some nasty values.
    formFieldName: string;

    // Controlled react component
    value?: number | string | null;
    onChange?: (newValue: number | string | null) => void;

    // General props
    valueType: "sectionGroup" | "productID";
    disabled?: boolean;
    errors?: IFieldError[];
}

/**
 * Render out a form group with selection UI for this input.
 * Renders a hidden input for compatibity with Gdn_Form.
 */
export const ProductSelectorFormGroup: React.FC<IProps> = (props: IProps) => {
    const { allProductLoadable, productsById } = useProducts();
    const { valueType } = props;
    const { subcommunitiesByProductID } = useSubcommunities();
    const options = useMemo(() => {
        return Object.values(productsById).map(productLoadable => {
            const { productID } = productLoadable.product;
            return {
                label: productLoadable.product.name,
                value: valueType === "sectionGroup" ? makeSiteSectionGroup({ productID }) : productID,
            };
        });
    }, [productsById, valueType]);
    const [modalOpen, setModalOpen] = useState(false);

    const [ownValue, setOwnValue] = useState<number | string | null>(
        typeof props.initialValue === "boolean" ? null : props.initialValue,
    );

    const setValue = props.onChange ?? setOwnValue;
    const value = props.value ?? ownValue;
    const selectedProduct = useMemo(() => {
        if (value == null) {
            return null;
        }

        let selectedProduct: ILoadedProduct | undefined;
        for (const [productID, product] of Object.entries(productsById)) {
            if (valueType === "sectionGroup" && value === makeSiteSectionGroup({ productID })) {
                selectedProduct = product;
                break;
            } else if (valueType === "productID" && value.toString() === productID.toString()) {
                selectedProduct = product;
                break;
            }
        }
        return selectedProduct;
    }, [productsById, value, valueType]);

    const currentComboBoxValue = useMemo(() => {
        if (!selectedProduct) {
            return null;
        }

        return {
            label: selectedProduct.product.name,
            value: value!,
        };
    }, [selectedProduct, value]);

    const ownSubcommunities = selectedProduct
        ? subcommunitiesByProductID.data?.[selectedProduct.product.productID] ?? []
        : null;
    const ownError = ownSubcommunities && ownSubcommunities.length === 0 ? [noSubcommunitiesFieldError()] : undefined;

    const errors = props.errors ?? ownError;

    return (
        <DashboardFormGroup
            label={t("Product")}
            description={
                <Translate
                    source="Associate a product with this subcommunity. Use the <0>Manage Products UI</0> to add, edit, or delete products."
                    c0={content => (
                        <Button
                            disabled={props.disabled}
                            baseClass={ButtonTypes.TEXT_PRIMARY}
                            onClick={() => setModalOpen(true)}
                        >
                            {content}
                        </Button>
                    )}
                />
            }
        >
            {modalOpen && <ProductManager onClose={() => setModalOpen(false)} asModal />}
            <input name={props.formFieldName} type="hidden" value={value != null ? value : ""} />
            <DashboardSelect
                errors={errors}
                disabled={allProductLoadable.status !== LoadStatus.SUCCESS || props.disabled}
                options={options}
                value={currentComboBoxValue!}
                onChange={value => {
                    setValue(value ? value.value : null);
                }}
            />
        </DashboardFormGroup>
    );
};
