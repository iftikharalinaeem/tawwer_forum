/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { IDeviceProps } from "@library/components/DeviceChecker";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import { LoadStatus } from "@library/@types/api";
import PageLoader from "@library/components/PageLoader";
import SearchForm from "./SearchForm";

interface IProps extends IDeviceProps {}

/**
 * Page component for search Page
 */
export class SearchPage extends React.Component<IProps> {
    public render() {
        return (
            <PageLoader status={LoadStatus.SUCCESS}>
                <SearchForm />
            </PageLoader>
        );
    }
}

export default withDevice(SearchPage);
