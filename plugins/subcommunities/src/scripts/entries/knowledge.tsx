/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { SearchFilterContextProvider } from "@library/contexts/SearchFilterContext";
import { ProductSearchFormFilter } from "@subcommunities/forms/ProductSeachFormFilter";
import React from "react";

SearchFilterContextProvider.addSearchFilter(
    "articles",
    <ProductSearchFormFilter searchDomain="articles" default="current" />,
);
SearchFilterContextProvider.addSearchFilter(
    "everywhere",
    <ProductSearchFormFilter searchDomain="everywhere" default="all" />,
);
