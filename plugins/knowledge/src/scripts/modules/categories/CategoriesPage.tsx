/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { t } from "@library/application";
import { ModalLink } from "@library/components/modal";
import { IDeviceProps } from "@library/components/DeviceChecker";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import CategoriesLayout from "@knowledge/modules/categories/components/CategoriesLayout";
import { dummyData } from "@knowledge/modules/categories/state/dummyData";
import { LoadStatus } from "@library/@types/api";
import PageLoader from "@library/components/PageLoader";

interface IProps extends IDeviceProps {}

/**
 * Page component for a flat category list.
 */
export default class CategoriesPage extends React.Component<IProps> {
    public render() {
        const categories = Object.values(dummyData.categoriesByID);
        return (
            <PageLoader data={{}} status={LoadStatus.SUCCESS}>
                <CategoriesLayout children={...categories as any} />
            </PageLoader>
        );
    }
}
