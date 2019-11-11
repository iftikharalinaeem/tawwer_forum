/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState } from "react";
import { IContentTranslatorProps, useLocaleInfo, TranslationPropertyType } from "@vanilla/i18n";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import Modal from "@library/modal/Modal";
import { TranslationGrid } from "../translationGrid/TranslationGrid";
import ModalSizes from "@library/modal/ModalSizes";
import Loader from "@library/loaders/Loader";
import { TranslateIcon } from "@library/icons/common";
import Frame from "@library/layout/frame/Frame";

export const ContentTranslator: React.FC<IContentTranslatorProps> = props => {
    let [displayModal, setDisplayModal] = useState(false);
    let [activeLocale, setActiveLocale] = useState<string | null>(null);
    const { currentLocale } = useLocaleInfo();
    if (!currentLocale) {
        return null;
    }

    let content = props.isLoading ? (
        <Loader size={200} />
    ) : (
        <TranslationGrid
            properties={props.properties}
            activeLocale={activeLocale}
            onActiveLocaleChange={setActiveLocale}
            onTranslationUpdate={newTranslations => {
                console.log("Has new translations", newTranslations);
            }}
            sourceLocale={currentLocale}
            existingTranslations={{}}
        />
    );

    if (props.isFullScreen) {
        // content = <Frame header={}/>
    }

    return (
        <>
            <Button baseClass={ButtonTypes.ICON} onClick={() => setDisplayModal(true)}>
                <TranslateIcon />
            </Button>
            {displayModal && (
                <Modal
                    titleID=""
                    exitHandler={() => setDisplayModal(false)}
                    size={props.isFullScreen ? ModalSizes.FULL_SCREEN : ModalSizes.LARGE}
                    scrollable
                />
            )}
        </>
    );
};
