/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useDispatch, useSelector } from "react-redux";
import { TranslationActions } from "./TranslationActions";
import apiv2 from "@library/apiv2";
import { ITranslationsGlobalStoreState } from "./translationReducer";
import { useMemo } from "react";
import { ITranslationProperty } from "@vanilla/i18n";
import { useThrowError } from "@vanilla/react-utils";

export function useTranslationActions() {
    const dispatch = useDispatch();
    const actions = useMemo(() => {
        return new TranslationActions(dispatch, apiv2);
    }, []);
    return actions;
}

export function useTranslationData() {
    return useSelector((state: ITranslationsGlobalStoreState) => state.translations);
}

export function validateProperties(properties: ITranslationProperty[]) {
    const recordTypes = properties.map(prop => prop.recordType);
    const set = new Set(recordTypes);
    if (set.size > 1) {
        const joined = Array.from(set).join(", ");
        throw new Error("<ContentTranslator /> can only work with 1 recordType at a time. Got mulitple " + joined);
    }
}
