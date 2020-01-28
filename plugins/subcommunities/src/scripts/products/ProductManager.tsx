/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LoadStatus } from "@library/@types/api/core";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Loader from "@library/loaders/Loader";
import { loaderClasses } from "@library/loaders/loaderStyles";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { t } from "@library/utility/appUtils";
import { useUniqueID } from "@library/utility/idUtils";
import { useProductActions } from "@subcommunities/products/ProductActions";
import { ProductManagerAddItem } from "@subcommunities/products/ProductManagerAddItem";
import { ProductManagerItem } from "@subcommunities/products/ProductManagerItem";
import { useProducts, useProductsState } from "@subcommunities/products/productSelectors";
import React, { useState } from "react";
import { ProductDeleteErrorModal } from "@subcommunities/products/ProductDeleteErrorModal";

interface IProps {
    onClose: () => void;
    asModal?: boolean;
}

/**
 * Component for managing all products for a site.
 */
export function ProductManager(props: IProps) {
    const titleID = useUniqueID("newProductTitle");

    const { allProductLoadable, productsById, submittingProducts } = useProducts();

    let bodyContent: React.ReactNode = null;
    let productCount: number | null = null;
    let canAddNew = false;
    switch (allProductLoadable.status) {
        case LoadStatus.PENDING:
        case LoadStatus.LOADING:
            const loaders = loaderClasses();
            bodyContent = <Loader loaderStyleClass={loaders.smallLoader} minimumTime={0} padding={18} />;
            break;
        case LoadStatus.SUCCESS:
            const productItems = Object.values(productsById).map(productLoadable => {
                if (productLoadable.product) {
                    return (
                        <ProductManagerItem key={productLoadable.product.productID} productLoadable={productLoadable} />
                    );
                }
            });
            const tempItems = Object.values(submittingProducts).map(product => {
                if (product.data) {
                    return <ProductManagerItem key={product.data.transactionID} productLoadable={product} />;
                }
            });
            bodyContent = [...productItems, ...tempItems];
            productCount = Object.values(productsById).length;
            canAddNew = true;
            break;
        case LoadStatus.ERROR:
            break;
    }

    // State for whether or not we want to show an input for adding a new product.
    const [showNewInput, setShowNewInput] = useState(false);
    const clearNewInput = () => setShowNewInput(false);

    let content = (
        <Frame
            header={
                <FrameHeader
                    titleID={titleID}
                    title={t("Manage Products")}
                    closeFrame={props.asModal ? props.onClose : undefined}
                />
            }
            body={
                <FrameBody>
                    {bodyContent}
                    {showNewInput && (
                        <ProductManagerItem
                            afterDelete={clearNewInput}
                            afterSubmit={clearNewInput}
                            onDismiss={clearNewInput}
                            isEditMode
                        />
                    )}
                </FrameBody>
            }
            footer={
                <FrameFooter>
                    <ProductManagerAddItem
                        disableAddButton={!canAddNew || showNewInput} // Disable when we are already open.
                        onAddClick={() => {
                            setShowNewInput(true);
                        }}
                        showEmptyMessage={productCount === 0}
                        showLoader={false}
                    />
                </FrameFooter>
            }
        />
    );

    if (props.asModal) {
        content = (
            <Modal isVisible={true} titleID={titleID} size={ModalSizes.MEDIUM} exitHandler={props.onClose}>
                {content}
            </Modal>
        );
    }
    return content;
}
