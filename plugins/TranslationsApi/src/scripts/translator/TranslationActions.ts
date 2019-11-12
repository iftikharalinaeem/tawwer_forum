/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { bindThunkAction } from "@library/redux/ReduxActions";
import { actionCreatorFactory } from "typescript-fsa";
import { IApiError } from "@library/@types/api/core";
import { ITranslationProperty } from "@vanilla/i18n";
import { ITranslationsGlobalStoreState } from "./translationReducer";
import { logWarning } from "@vanilla/utils";
import { validateProperties } from "./translationHooks";

const createAction = actionCreatorFactory("@@translations");

export interface ITranslationApiItem {
    locale: string;
    propertyName: string;
    propertyType: string;
    translation: string;
    recordType: string;
    recordID?: number;
    recordKey?: string;
    translationPropertyKey: string;
}

interface IGetTranslationsRequest {
    recordType?: string;
    recordID?: number;
    recordIDs?: number[];
    recordKey?: string;
    recordKeys?: string[];
    locale: string;
}

type IGetTranslationsResponse = ITranslationApiItem[];
type IPutTranslationsRequest = ITranslationApiItem[];
interface IPutTranslationsResponse {}

export function makeTranslationKey(
    property: Pick<ITranslationApiItem, "propertyName" | "recordType" | "recordID" | "recordKey">,
) {
    return `${property.recordType}.${property.recordID || property.recordKey}.${property.propertyName}`;
}

export class TranslationActions extends ReduxActions<ITranslationsGlobalStoreState> {
    ///
    /// Constants
    ///

    public static updateForm = createAction<{ field: string; translation: string }>("UPDATE_FORM");

    public static clearForm = createAction("CLEAR_FORM");

    public static init = createAction<{
        resource: string;
        translationLocale: string;
    }>("INIT");

    public static getTranslationsACs = createAction.async<IGetTranslationsRequest, IGetTranslationsResponse, IApiError>(
        "GET",
    );

    public static putTranslationsACs = createAction.async<IPutTranslationsRequest, IPutTranslationsResponse, IApiError>(
        "PUT",
    );

    ///
    /// Simple actions
    ///

    public updateForm = (field: string, translation: string) => {
        this.dispatch(TranslationActions.updateForm({ field, translation }));
    };

    public clearForm = this.bindDispatch(TranslationActions.clearForm);

    public init = this.bindDispatch(TranslationActions.init);

    ///
    /// Thunks
    ///

    /**
     * Get all translations from the API based on the given properties.
     */
    public getTranslationsForProperties = async (properties: ITranslationProperty[]) => {
        const { translationLocale } = this.getState().translations;
        const firstProperty = properties[0];
        if (!firstProperty) {
            logWarning("Attempted to fetch translations, but no properties were provided");
            return;
        }

        if (!translationLocale) {
            logWarning("Attempted to fetch translations, but no locale was configured.");
            return;
        }

        validateProperties(properties);

        const recordIDs = new Set(properties.map(prop => prop.recordID!).filter(id => id != null));
        const recordKeys = new Set(properties.map(prop => prop.recordKey!).filter(key => key != null));

        const query: IGetTranslationsRequest = {
            locale: translationLocale,
            recordType: firstProperty.recordType,
        };

        if (recordIDs.size > 0) {
            query.recordIDs = Array.from(recordIDs);
        } else if (recordKeys.size > 0) {
            query.recordKeys = Array.from(recordKeys);
        }

        return await this.getTranslations(query);
    };

    /**
     * Publish all form values to the API based on the given properties.
     */
    public publishForm = async (properties: ITranslationProperty[]) => {
        const { translationLocale } = this.getState().translations;
        const publishFieldValues: ITranslationApiItem[] = [];
        const { formTranslations } = this.getState().translations;

        for (const [key, translation] of Object.entries(formTranslations)) {
            const property = properties.find(prop => {
                const propKey = makeTranslationKey(prop);
                return key === propKey;
            });

            if (property) {
                publishFieldValues.push({
                    translationPropertyKey: makeTranslationKey(property),
                    ...property,
                    locale: translationLocale!,
                    translation,
                });
            }
        }

        await this.putTranslations(publishFieldValues);

        // Simulate getting the saved values back.
        const firstField = publishFieldValues[0];
        if (!firstField) {
            return;
        }

        this.dispatch(
            TranslationActions.getTranslationsACs.done({
                params: { locale: firstField.locale },
                result: publishFieldValues,
            }),
        );
    };

    /**
     * Get translations from the /api/v2/translations endpoint.
     */
    public getTranslations = (params: IGetTranslationsRequest) => {
        const { resource } = this.getState().translations;
        const thunk = bindThunkAction(TranslationActions.getTranslationsACs, async () => {
            const response = await this.api.get(`/translations/${resource}`, { params });
            return response.data;
        })(params);

        return this.dispatch(thunk);
    };

    /**
     * Submit translations from the /api/v2/translations endpoint.
     */
    public putTranslations = (translations: IPutTranslationsRequest) => {
        const { resource } = this.getState().translations;
        const thunk = bindThunkAction(TranslationActions.putTranslationsACs, async () => {
            const response = await this.api.put(`/translations/${resource}`, translations);
            return response.data;
        })(translations);

        return this.dispatch(thunk);
    };
}
