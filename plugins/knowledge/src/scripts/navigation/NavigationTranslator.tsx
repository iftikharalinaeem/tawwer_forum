/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useDebugValue } from "react";
import {
    useContentTranslator,
    ITranslationProperty,
    TranslationPropertyType,
    t,
    getCurrentLocale,
} from "@vanilla/i18n";
import { useSelector } from "react-redux";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import NavigationModel, {
    INormalizedNavigationItem,
    KbRecordType,
    IKbNavigationItem,
} from "@knowledge/navigation/state/NavigationModel";
import { KB_RESOURCE_NAME } from "@knowledge/constants";
import { LoadStatus } from "@library/@types/api/core";
import { Permission } from "@library/features/users/Permission";

interface IProps {
    kbID: number;
    sourceLocale: string | null;
    activeLocale: string | null;
}

export function OrganizeCategoriesTranslator(props: IProps) {
    const { Translator, shouldDisplay } = useContentTranslator();
    // const itemsLoadable = useNavigationCategoriesForKB(props.kbID);
    const items = useNavigationCategoriesForKB(props.kbID);

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
            title={t("Translate Categories")}
            sourceLocale={props.sourceLocale}
            activeLocale={props.activeLocale}
        />
    );
}

//function navItemToTranslationProperty(item: IKbNavigationItem): ITranslationProperty {
function navItemToTranslationProperty(item: IKbNavigationItem): ITranslationProperty {
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
        (state: IKnowledgeAppStoreState) => state.knowledge.navigation.translationSourceNavItems.status,
        //  (state: IKnowledgeAppStoreState) => state.knowledge.navigation.fetchStatusesByKbID[kbID],
    );

    return status;
}

function useNavigationCategoriesForKB(kbID: number) {
    //const navItemsByID = useSelector((state: IKnowledgeAppStoreState) => state.knowledge.navigation.navigationItems);
    const itemsForKbLoadable = useSelector(
        (state: IKnowledgeAppStoreState) => state.knowledge.navigation.translationSourceNavItems,
    );
    const itemsForKb = Object.values(itemsForKbLoadable).filter(
        item =>
            item &&
            item.knowledgeBaseID === kbID &&
            item.recordType === KbRecordType.CATEGORY &&
            item.parentID !== NavigationModel.SYNTHETIC_ROOT.recordID,
    );

    // Instead of pulling form main data

    const status = useStatus(kbID);

    if (status === LoadStatus.PENDING) {
    }

    useDebugValue({
        kbID,
        itemsForKb,
    });

    return itemsForKb;
}
