/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardSelect } from "@dashboard/forms/DashboardSelect";
import { LoadStatus } from "@library/@types/api/core";
import Translate from "@library/content/Translate";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { t } from "@library/utility/appUtils";
import { ProductManager } from "@subcommunities/products/ProductManager";
import { useProducts } from "@subcommunities/products/productSelectors";
import React, { useMemo, useState } from "react";

interface IProps {}

/**
 * Render out a form group with selection UI for this input.
 * Renders a hidden input for compatibity with Gdn_Form.
 */
export function ProductSelectorFormGroup(props: IProps) {
    const { allProductLoadable, productsById } = useProducts();
    const options = useMemo(() => {
        return Object.values(productsById)
            .filter(product => !!product.data)
            .map(product => {
                return {
                    label: product.data!.name,
                    value: product.data!.productID,
                };
            });
    }, [productsById]);
    const [modalOpen, setModalOpen] = useState(false);
    const [value, setValue] = useState<IComboBoxOption | undefined>(undefined);

    return (
        <DashboardFormGroup
            label={t("Product Name")}
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
            <input type="hidden" value={value ? value.value : ""} />
            <DashboardSelect
                disabled={allProductLoadable.status !== LoadStatus.SUCCESS}
                options={options}
                value={value}
                onChange={value => setValue(value)}
            />
        </DashboardFormGroup>
    );
}
