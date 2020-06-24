/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useDebugValue } from "react";
import { useContentTranslator, ITranslationProperty, TranslationPropertyType, t } from "@vanilla/i18n";
import { useSelector } from "react-redux";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import NavigationModel, {
    INormalizedNavigationItem,
    KbRecordType,
    IKbNavigationItem,
} from "@knowledge/navigation/state/NavigationModel";
import { useNavigationActions } from "@knowledge/navigation/state/NavigationActions";
import { KB_RESOURCE_NAME } from "@knowledge/constants";
import { LoadStatus } from "@library/@types/api/core";

interface IProps {
    kbID: number;
    sourceLocale: string;
    activeLocale: string;
}

export function OrganizeCategoriesTranslator(props: IProps) {
    const { getNavigationFlat } = useNavigationActions();
    const { Translator, shouldDisplay } = useContentTranslator();
    const status = useStatus(props.kbID);

    let items = useNavigationCategoriesForKB(props.kbID);

    if (!shouldDisplay) {
        return null;
    }
    const isLoading = status === LoadStatus.LOADING;

    return (
        <Translator
            afterSave={() => {
                getNavigationFlat(props.kbID, true);
            }}
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
    );

    return status;
}

function useNavigationCategoriesForKB(kbID: number): IKbNavigationItem[] {
    const itemsForKbLoadable = useSelector(
        (state: IKnowledgeAppStoreState) => state.knowledge.navigation.translationSourceNavItems,
    );

    let itemsForKb: INormalizedNavigationItem[];

    const status = useStatus(kbID);

    itemsForKb = Object.values(itemsForKbLoadable).filter(
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

export default OrganizeCategoriesTranslator;
