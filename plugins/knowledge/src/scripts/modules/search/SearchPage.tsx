/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { t } from "@library/application";
import { IDeviceProps } from "@library/components/DeviceChecker";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import { LoadStatus } from "@library/@types/api";
import PageLoader from "@library/components/PageLoader";
import DocumentTitle from "@library/components/DocumentTitle";
import SearchForm from "./SearchForm";

interface IProps extends IDeviceProps {}

/**
 * Page component for search Page
 */
export class SearchPage extends React.Component<IProps> {
    public render() {
        return (
            <PageLoader status={LoadStatus.SUCCESS}>
                <DocumentTitle title={t("Search Results")}>
                    <SearchForm />
                </DocumentTitle>
            </PageLoader>
        );
    }
}

export default withDevice(SearchPage);
