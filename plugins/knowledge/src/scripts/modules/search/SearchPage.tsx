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
import { LoadStatus } from "@library/@types/api";
import PageLoader from "@library/components/PageLoader";
import { ICrumb } from "@library/components/Breadcrumbs";
import { IStoreState } from "@knowledge/state/model";
import { ICategoriesPageState } from "@knowledge/modules/categories/CategoriesPageReducer";
import DocumentTitle from "@library/components/DocumentTitle";
import { IKbCategoryFragment } from "@knowledge/@types/api";
import SearchLayout, { ISearchState } from "./components/SearchLayout";
import { IResult } from "@knowledge/modules/common/SearchResult";

interface IProps extends IDeviceProps, ISearchState {}

/**
 * Page component for search Page
 */
export class SearchPage extends React.Component<IProps> {
    public render() {
        return (
            <PageLoader status={LoadStatus.SUCCESS}>
                <DocumentTitle title={t("Search Results")}>
                    <SearchLayout {...this.props} />
                </DocumentTitle>
            </PageLoader>
        );
    }
}

export default withDevice(SearchPage);
