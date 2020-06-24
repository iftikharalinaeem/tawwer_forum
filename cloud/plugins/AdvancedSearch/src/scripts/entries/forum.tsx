/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import "../search/legacySearch";
import { SearchContextProvider } from "@library/contexts/SearchContext";
import { AdvancedSearchOptionProvider } from "../search/AdvancedSearchOptionProvider";

SearchContextProvider.setOptionProvider(new AdvancedSearchOptionProvider());
