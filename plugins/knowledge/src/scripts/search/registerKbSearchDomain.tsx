/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { TypeArticlesIcon } from "@vanilla/library/src/scripts/icons/searchIcons";
import { SearchFormContextProvider } from "@vanilla/library/src/scripts/search/SearchFormContext";
import { ISearchForm } from "@vanilla/library/src/scripts/search/searchTypes";
import { onReady, t } from "@vanilla/library/src/scripts/utility/appUtils";
import React from "react";
import { IKnowledgeSearchTypes } from "@knowledge/search/knowledgeSearchTypes";
import { SearchFilterPanelArticles } from "@knowledge/search/SearchFilterPanelArticles";
import { registerReducer } from "@vanilla/library/src/scripts/redux/reducerRegistry";
import knowledgeReducer from "@knowledge/state/reducer";

registerReducer("knowledge", knowledgeReducer);

export function registerKbSearchDomain() {
    onReady(() => {
        SearchFormContextProvider.addPluggableDomain({
            key: "knowledge",
            name: t("Articles"),
            icon: <TypeArticlesIcon />,
            getAllowedFields: () => {
                return ["knowledgeBaseID", "includeDeleted"];
            },
            transformFormToQuery: (form: ISearchForm<IKnowledgeSearchTypes>) => {
                const query = {
                    ...form,
                };
                if (query.knowledgeBaseOption) {
                    query.knowledgeBaseID = query.knowledgeBaseOption.value as number;
                }
                return query;
            },
            getRecordTypes: () => {
                return ["article"];
            },
            PanelComponent: SearchFilterPanelArticles,
            getDefaultFormValues: () => {
                return {
                    includeDeleted: false,
                };
            },
        });

        SearchFormContextProvider.addSubType({
            label: t("Article"),
            icon: <TypeArticlesIcon />,
            recordType: "article",
            type: "article",
        });
    });
}
