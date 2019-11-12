/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useDebugValue } from "react";
import { useContentTranslator, ITranslationProperty, TranslationPropertyType } from "@vanilla/i18n";
import { useSelector } from "react-redux";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import NavigationModel, { INormalizedNavigationItem, KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import { KB_RESOURCE_NAME } from "@knowledge/constants";
import { LoadStatus } from "@library/@types/api/core";
import { Permission } from "@library/features/users/Permission";

interface IProps {
    kbID: number;
}

export function OrganizeCategoriesTranslator(props: IProps) {
    const { Translator, shouldDisplay } = useContentTranslator();
    const items = useNavigationCategoriesForKB(props.kbID);
    const status = useStatus(props.kbID);

    if (!shouldDisplay) {
        return null;
    }

    const isLoading = status === LoadStatus.LOADING;

    return (
        <Translator
            properties={items.map(navItemToTranslationProperty)}
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
        item =>
            item &&
            item.knowledgeBaseID === kbID &&
            item.recordType === KbRecordType.CATEGORY &&
            item.parentID !== NavigationModel.SYNTHETIC_ROOT.recordID,
    );
    useDebugValue({
        kbID,
        itemsForKb,
    });
    return itemsForKb;
}
