/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useDebugValue } from "react";
import { useContentTranslator, ITranslationProperty, TranslationPropertyType } from "@vanilla/i18n";
import { useSelector } from "react-redux";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { INormalizedNavigationItem, KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import { KB_RESOURCE_NAME } from "@knowledge/constants";
import { LoadStatus } from "@library/@types/api/core";

interface IProps {
    kbID: number;
}

export function OrganizeCategoriesTranslator(props: IProps) {
    const { Translator, shouldDisplay } = useContentTranslator();
    const items = useNavigationCategoriesForKB(props.kbID);
    const status = useStatus(props.kbID);

    const isLoading = status === LoadStatus.LOADING;

    if (!shouldDisplay) {
        return null;
    }

    return (
        <Translator
            properties={items.map(navItemToTranslationProperty)}
            afterSave={() => {}}
            onDismiss={() => {}}
            isLoading={isLoading}
            isFullScreen
            resource={KB_RESOURCE_NAME}
        />
    );
}

function navItemToTranslationProperty(item: INormalizedNavigationItem): ITranslationProperty {
    return {
        propertyName: "name",
        propertyType: TranslationPropertyType.TEXT,
        recordType: item.recordType,
        recordID: item.recordID,
        sourceText: item.name,
        propertyValidation: {
            minLength: 1,
        },
    };
}

function useStatus(kbID: number) {
    const status = useSelector(
        (state: IKnowledgeAppStoreState) => state.knowledge.navigation.fetchStatusesByKbID[kbID],
    );

    return status;
}

function useNavigationCategoriesForKB(kbID: number) {
    const navItemsByID = useSelector((state: IKnowledgeAppStoreState) => state.knowledge.navigation.navigationItems);
    const itemsForKb = Object.values(navItemsByID).filter(
        item => item && item.knowledgeBaseID === kbID && item.recordType === KbRecordType.CATEGORY,
    );
    useDebugValue(kbID);
    return itemsForKb;
}
