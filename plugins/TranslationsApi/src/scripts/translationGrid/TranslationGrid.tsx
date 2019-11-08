/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { panelListClasses } from "@library/layout/panelListStyles";
import { useUniqueID } from "@library/utility/idUtils";
import { ITranslationProperty, LocaleDisplayer } from "@vanilla/i18n";
import classNames from "classnames";
import React, { useCallback, useEffect, useState } from "react";
import { translationGridClasses } from "./TranslationGridStyles";
import { TranslationProperty } from "./TranslationProperty";
import { TranslationGridLocaleChooser } from "./TranslationGridLocaleChooser";
import Translate from "@library/content/Translate";

interface ITranslations {
    [propertyKey: string]: string;
}

export interface ITranslationGrid {
    properties: ITranslationProperty[];
    existingTranslations: ITranslations;
    inScrollingContainer?: boolean;
    dateUpdated?: string;
    sourceLocale: string;
}

function useTranslationState(initialTranslations: ITranslations) {
    const [inProgressTranslations, setInProgressTranslations] = useState(initialTranslations);
    useEffect(() => {
        setInProgressTranslations(initialTranslations);
    }, [initialTranslations]);

    const updateTranslationDraft = useCallback(
        (propertyKey: string, translation: string) => {
            setInProgressTranslations({
                ...inProgressTranslations,
                [propertyKey]: translation,
            });
        },
        [setInProgressTranslations, inProgressTranslations],
    );

    return { inProgressTranslations, updateTranslationDraft };
}

/**
 * Translation UI
 * @param props
 * @constructor
 */
export function TranslationGrid(props: ITranslationGrid) {
    const { existingTranslations } = props;
    const { inProgressTranslations, updateTranslationDraft } = useTranslationState(existingTranslations);

    const classesPanelList = panelListClasses();
    const { properties, inScrollingContainer = false } = props;
    const classes = translationGridClasses();

    return (
        <div className={classNames(classes.root, { [classes.inScrollContainer]: inScrollingContainer })}>
            <div className={classes.frame}>
                <div className={classes.header}>
                    <div className={classNames(classes.leftCell, classes.headerLeft)}>
                        <Translate
                            source="<0/> (Source)"
                            c0={
                                <LocaleDisplayer
                                    displayLocale={props.sourceLocale}
                                    localeContent={props.sourceLocale}
                                />
                            }
                        />
                    </div>
                    <div className={classNames(classes.rightCell, classes.headerRight)}>
                        <div className={classes.languageDropdown}>
                            <div className={classNames("otherLanguages", "panelList", classesPanelList.root)}>
                                <TranslationGridLocaleChooser sourceLocale={props.sourceLocale} selectedLocale="ca" />
                            </div>
                        </div>
                    </div>
                </div>
                <div className={classes.body}>
                    {properties.map((property, i) => (
                        <TranslationProperty
                            key={property.translationPropertyKey}
                            isFirst={i === 0}
                            isLast={i === properties.length - 1}
                            property={property}
                            existingTranslation={existingTranslations[property.translationPropertyKey] || null}
                            translationValue={inProgressTranslations[property.translationPropertyKey]}
                            onTranslationChange={updateTranslationDraft}
                        />
                    ))}
                </div>
            </div>
        </div>
    );
}
