/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { LoadStatus } from "@library/@types/api/core";
import PageLoader from "@library/routing/PageLoader";
import SearchForm from "./SearchForm";
import { IDeviceProps } from "@library/layout/DeviceContext";
import UnifySearchForm from "@knowledge/modules/search/UnifySearchForm";

interface IProps extends IDeviceProps {}

/**
 * Page component for search Page
 */
export class SearchPage extends React.Component<IProps> {
    public render() {
        return (
            <PageLoader status={LoadStatus.SUCCESS}>
                {/* <SearchForm /> */}
                <UnifySearchForm />
            </PageLoader>
        );
    }
}

export default SearchPage;
