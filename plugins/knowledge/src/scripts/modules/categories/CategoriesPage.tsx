/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/application";
import { ModalLink } from "@library/components/modal";
import { IDeviceProps } from "@library/components/DeviceChecker";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import { CategoriesLayout } from "@knowledge/modules/categories/components/CategoriesLayout";
import { dummyData } from "@knowledge/modules/categories/state/dummyData";

interface IProps extends IDeviceProps {}

/**
 * Page component for a flat category list.
 */
export class CategoriesPage extends React.Component<IProps> {
    public render() {
        const categories = Object.values(dummyData.categoriesByID);
        return <CategoriesLayout children={categories} />;
    }
}

export default withDevice(CategoriesPage);
