/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState, useRef, useLayoutEffect, useEffect } from "react";
import { ILoadable, LoadStatus, IFieldError, IApiError } from "@library/@types/api/core";
import { IProduct, IProductDeleteError } from "@subcommunities/products/productTypes";
import { TempProduct, ILoadedProduct } from "@subcommunities/products/productReducer";
import { useProductActions } from "@subcommunities/products/ProductActions";
import { productManagerClasses } from "@subcommunities/products/productManagerStyles";
import { useFocusWatcher } from "@vanilla/react-utils";
import { TextInput } from "@library/forms/TextInput";
import { t } from "@library/utility/appUtils";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { PlusCircleIcon, EditIcon, DeleteIcon } from "@library/icons/common";
import { ProductDeleteErrorModal } from "@subcommunities/products/ProductDeleteErrorModal";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { useSubcommunities } from "@subcommunities/subcommunities/subcommunitySelectors";
import ErrorMessages from "@library/forms/ErrorMessages";
import { noSubcommunitiesFieldError } from "@subcommunities/subcommunities/subcommunityErrors";

interface IProps {
    productLoadable?: ILoadedProduct | ILoadable<TempProduct>;
    afterSubmit?: () => void;
    afterDelete?: () => void;
    onDismiss?: () => void;
    isEditMode?: boolean;
}

/**
 * Component representing an item row in the product manager.
 * - Displays an existing item (or temporary item).
 * - Displays input for editing an existing item.
 * - POST/PATCH/DELETE apiv2 integration.
 */
export function ProductManagerItem(props: IProps) {
    ///
    /// Data unwrapping
    ///
    const loadedProduct: ILoadedProduct | null =
        props.productLoadable && "product" in props.productLoadable ? props.productLoadable : null;
    const actualProduct: IProduct | null = loadedProduct ? loadedProduct.product : null;
    props.productLoadable && "product" in props.productLoadable ? props.productLoadable.product : null;
    const tempProduct: TempProduct | null =
        props.productLoadable && "data" in props.productLoadable && props.productLoadable.data
            ? props.productLoadable.data
            : null;
    const initialProductName = actualProduct ? actualProduct.name : "";

    const { subcommunitiesByProductID } = useSubcommunities();
    const relatedSubcommunities = actualProduct?.productID
        ? subcommunitiesByProductID.data?.[actualProduct.productID]
        : null;
    const hasNoSubcommunities = (relatedSubcommunities?.length ?? 0) === 0 ?? true;

    /// Locale State
    const { postProduct, patchProduct, deleteProduct, clearDeleteError } = useProductActions();
    const [isEditMode, setIsEditMode] = useState(!!props.isEditMode);
    const [inputValue, setInputValue] = useState(initialProductName);

    ///
    /// Focus Handling
    ///
    const rootRef = useRef<HTMLLIElement>(null);
    const inputRef = useRef<HTMLInputElement>(null);
    const deleteIconRef = useRef<HTMLButtonElement>(null);

    // Focus the input when it opens.
    useLayoutEffect(() => {
        inputRef.current && inputRef.current.focus();
    }, [isEditMode, inputRef]);

    // Dismiss the edit mode if we lose focus.
    useFocusWatcher(rootRef, newFocus => {
        if (!newFocus) {
            setIsEditMode(false);
        }
    });

    // Dismiss the input if we have no product and we're not in edit mode.
    // Clears away "new" items that are no longer being used.
    useEffect(() => {
        if (!actualProduct && !isEditMode && props.onDismiss) {
            props.onDismiss();
        }
    }, [actualProduct, isEditMode, props, props.onDismiss]);

    ///
    /// Event Handling
    ///
    const onEditClick = () => {
        setIsEditMode(true);
    };

    // Post to the products API and call are after submit handler.
    const onSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        e.stopPropagation();
        if (actualProduct) {
            void patchProduct({
                productID: actualProduct.productID,
                name: inputValue,
            });
        } else {
            void postProduct({ name: inputValue });
        }
        setIsEditMode(false);
        props.afterSubmit && props.afterSubmit();
    };

    // Post to the delete endpoint and call the after delete handler.
    const onDelete = () => {
        if (actualProduct) {
            void deleteProduct({ productID: actualProduct.productID });
        }
        props.afterDelete && props.afterDelete();
    };

    const errors: Array<IApiError | IFieldError> = [];

    if (props.productLoadable && "error" in props.productLoadable && props.productLoadable.error) {
        // This is a temp item so the other errors shouldn't apply.
        errors.push(props.productLoadable.error);
    } else {
        if (hasNoSubcommunities) {
            errors.push(noSubcommunitiesFieldError());
        }

        if (loadedProduct?.patchProduct.error) {
            errors.push(loadedProduct?.patchProduct.error);
        }

        if (loadedProduct?.deleteProduct.error) {
            errors.push(loadedProduct?.deleteProduct.error);
        }
    }
    ///
    /// Rendering
    ///
    const classes = productManagerClasses();
    return (
        <>
            {loadedProduct && loadedProduct.deleteProduct.error && (
                <ProductDeleteErrorModal
                    isVisible={true}
                    elementToFocusOnExit={deleteIconRef.current}
                    onClose={() => clearDeleteError(loadedProduct.product.productID)}
                    product={loadedProduct.product}
                    errorData={loadedProduct.deleteProduct.error}
                />
            )}
            <li className={classes.row} ref={rootRef}>
                <form onSubmit={onSubmit} className={classes.item}>
                    {isEditMode ? (
                        <TextInput
                            placeholder={t("Product Name")}
                            className={classes.input}
                            value={inputValue}
                            inputRef={inputRef}
                            onChange={event => {
                                event.preventDefault();
                                setInputValue(event.target.value);
                            }}
                        />
                    ) : (
                        <span className={classes.itemName}>
                            {actualProduct ? actualProduct.name : tempProduct ? tempProduct.name : null}
                            {errors.length > 0 && <ErrorMessages className={classes.error} errors={errors} />}
                        </span>
                    )}
                    <div className={classes.itemActions}>
                        {isEditMode ? (
                            <Button baseClass={ButtonTypes.ICON} submit disabled={inputValue.length < 1}>
                                <PlusCircleIcon />
                            </Button>
                        ) : (
                            <Button baseClass={ButtonTypes.ICON} onClick={onEditClick}>
                                {loadedProduct && loadedProduct.patchProduct.status === LoadStatus.LOADING ? (
                                    <ButtonLoader buttonType={ButtonTypes.ICON} />
                                ) : (
                                    <EditIcon />
                                )}
                            </Button>
                        )}
                        <Button baseClass={ButtonTypes.ICON} onClick={onDelete} buttonRef={deleteIconRef}>
                            {loadedProduct && loadedProduct.deleteProduct.status === LoadStatus.LOADING ? (
                                <ButtonLoader buttonType={ButtonTypes.ICON} />
                            ) : (
                                <DeleteIcon />
                            )}
                        </Button>
                    </div>
                </form>
            </li>
        </>
    );
}
