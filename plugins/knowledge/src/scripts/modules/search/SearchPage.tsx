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
import CategoriesLayout from "@knowledge/modules/categories/components/CategoriesLayout";
import { LoadStatus } from "@library/@types/api";
import PageLoader from "@library/components/PageLoader";
import { ICrumb } from "@library/components/Breadcrumbs";
import { IStoreState } from "@knowledge/state/model";
import CategoryModel from "@knowledge/modules/categories/CategoryModel";
import { match } from "react-router";
import { ICategoriesPageState } from "@knowledge/modules/categories/CategoriesPageReducer";
import apiv2 from "@library/apiv2";
import CategoriesPageActions from "@knowledge/modules/categories/CategoriesPageActions";
import { connect } from "react-redux";
import NotFoundPage from "@library/components/NotFoundPage";
import DocumentTitle from "@library/components/DocumentTitle";
import { IKbCategoryFragment } from "@knowledge/@types/api";
import { dummySearchResults } from "./state/dummySearchResults";
import SearchLayout, { ISearchState } from "./components/SearchLayout";
import { IResult } from "@knowledge/modules/common/SearchResult";

interface IProps extends IDeviceProps {}

interface IState extends ISearchState {}

/**
 * Page component for search Page
 */
export class SearchPage extends React.Component<IProps, IState> {
    public render() {
        return (
            <PageLoader status={LoadStatus.SUCCESS}>
                <DocumentTitle title={t("Search Results")}>
                    <SearchLayout {...this.state} />
                </DocumentTitle>
            </PageLoader>
        );
    }
}

export default withDevice(SearchPage);
