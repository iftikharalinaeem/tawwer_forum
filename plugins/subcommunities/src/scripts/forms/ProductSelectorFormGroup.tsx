/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardSelect } from "@dashboard/forms/DashboardSelect";
import { LoadStatus } from "@library/@types/api/core";
import Translate from "@library/content/Translate";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { t } from "@library/utility/appUtils";
import { ProductManager } from "@subcommunities/products/ProductManager";
import { useProducts } from "@subcommunities/products/productSelectors";
import React, { useMemo, useState } from "react";
import { makeSiteSectionGroup } from "@subcommunities/products/productTypes";
import { ILoadedProduct } from "@subcommunities/products/productReducer";

interface IProps {
    initialValue: number | null | boolean; // Gdn_Form can give us some nasty values.
    formFieldName: string;
    valueType: "sectionGroup" | "productID";
}

/**
 * Render out a form group with selection UI for this input.
 * Renders a hidden input for compatibity with Gdn_Form.
 */
export const ProductSelectorFormGroup: React.FC<IProps> = (props: IProps) => {
    const { allProductLoadable, productsById } = useProducts();
    const options = useMemo(() => {
        return Object.values(productsById).map(productLoadable => {
            const { productID } = productLoadable.product;
            return {
                label: productLoadable.product.name,
                value: props.valueType === "sectionGroup" ? makeSiteSectionGroup({ productID }) : productID,
            };
        });
    }, [productsById]);
    const [modalOpen, setModalOpen] = useState(false);

    const [value, setValue] = useState<number | string | null>(
        typeof props.initialValue === "boolean" ? null : props.initialValue,
    );
    const currentComboBoxValue = useMemo(() => {
        if (value == null) {
            return null;
        }

        let selectedProduct: ILoadedProduct | undefined;
        for (const [productID, product] of Object.entries(productsById)) {
            if (props.valueType === "sectionGroup" && value === makeSiteSectionGroup({ productID })) {
                selectedProduct = product;
                break;
            } else if (props.valueType === "productID" && value === productID) {
                selectedProduct = product;
                break;
            }
        }
        if (!selectedProduct) {
            return null;
        }

        return {
            label: selectedProduct.product.name,
            value,
        };
    }, [value, productsById]);

    return (
        <DashboardFormGroup
            label={t("Product")}
            description={
                <Translate
                    source="Assosciate a product with this Subcommunity. <0>Use the management UI</0> to replace add, edit, or delete products."
                    c0={content => (
                        <Button baseClass={ButtonTypes.TEXT_PRIMARY} onClick={() => setModalOpen(true)}>
                            {content}
                        </Button>
                    )}
                />
            }
        >
            {modalOpen && <ProductManager onClose={() => setModalOpen(false)} asModal />}
            <input name={props.formFieldName} type="hidden" value={value != null ? value : ""} />
            <DashboardSelect
                disabled={allProductLoadable.status !== LoadStatus.SUCCESS}
                options={options}
                value={currentComboBoxValue!}
                onChange={value => {
                    setValue(value ? value.value : null);
                }}
            />
        </DashboardFormGroup>
    );
};
