/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { productManagerClasses } from "@subcommunities/products/productManagerStyles";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { t } from "@library/utility/appUtils";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";

interface IProps {
    onAddClick: () => void;
    showEmptyMessage: boolean;
    disableAddButton: boolean;
    showLoader: boolean;
}

export function ProductManagerAddItem(props: IProps) {
    const classes = productManagerClasses();

    return (
        <li className={classes.row}>
            <div className={classes.emptyMessage}>
                {props.showEmptyMessage && !props.showLoader && t("There are no products yet.")}
            </div>
            {props.showLoader && <ButtonLoader alignLeft className={classes.loader} />}
            <Button
                onClick={props.onAddClick}
                disabled={props.disableAddButton}
                baseClass={ButtonTypes.DASHBOARD_PRIMARY}
            >
                {t("Add")}
            </Button>
        </li>
    );
}
