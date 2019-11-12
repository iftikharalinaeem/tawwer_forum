/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { TranslateIcon } from "@library/icons/common";
import Container from "@library/layout/components/Container";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Heading from "@library/layout/Heading";
import Loader from "@library/loaders/Loader";
import Modal from "@library/modal/Modal";
import ModalConfirm from "@library/modal/ModalConfirm";
import ModalSizes from "@library/modal/ModalSizes";
import DocumentTitle from "@library/routing/DocumentTitle";
import { useUniqueID } from "@library/utility/idUtils";
import { IContentTranslatorProps, t, useLocaleInfo } from "@vanilla/i18n";
import React, { useState } from "react";
import { TranslationGrid } from "../translationGrid/TranslationGrid";
import { contentTranslatorClasses } from "./contentTranslatorStyles";
import { ContentTranslaterFullHeader } from "./ContentTranslatorFullHeader";

const EMPTY_TRANSLATIONS = {};

export const ContentTranslator = (props: IContentTranslatorProps) => {
    let [displayModal, setDisplayModal] = useState(false);
    let [activeLocale, setActiveLocale] = useState<string | null>(null);
    const { currentLocale } = useLocaleInfo();
    const titleID = useUniqueID("translateCategoriesTitle");
    let [showCloseConfirm, setShowCloseConfirm] = useState(false);
    let [showChangeConfirm, setShowChangeConfirm] = useState<string | null>(null);
    let [hasUnsavedChanges, setHasUnsavedChanges] = useState(false);

    const promptCloseConfirmation = () => {
        if (hasUnsavedChanges) {
            setShowCloseConfirm(true);
        } else {
            setDisplayModal(false);
        }
    };

    const closeSelf = () => {
        setHasUnsavedChanges(false);
        setShowCloseConfirm(false);
        setDisplayModal(false);
    };

    if (!currentLocale) {
        return null;
    }

    const classes = contentTranslatorClasses();

    let content = props.isLoading ? (
        <Loader size={200} />
    ) : (
        <TranslationGrid
            key={activeLocale || undefined}
            properties={props.properties}
            activeLocale={activeLocale}
            onActiveLocaleChange={locale => {
                if (hasUnsavedChanges) {
                    setShowChangeConfirm(locale);
                } else {
                    setActiveLocale(locale);
                }
            }}
            onTranslationUpdate={newTranslations => {
                setHasUnsavedChanges(true);
                console.log("Has new translations", newTranslations);
            }}
            sourceLocale={currentLocale}
            existingTranslations={EMPTY_TRANSLATIONS}
        />
    );

    const title = t("Translate Categories");
    if (props.isFullScreen) {
        content = (
            <>
                <ContentTranslaterFullHeader onBack={promptCloseConfirmation} onSave={promptCloseConfirmation} />
                <Container className={classes.content}>
                    <DocumentTitle title={title}>
                        <Heading id={titleID} depth={1} renderAsDepth={2} title={title} />
                    </DocumentTitle>
                    {content}
                </Container>
            </>
        );
    } else {
        content = (
            <Frame
                header={<FrameHeader titleID={titleID} title={title} closeFrame={promptCloseConfirmation} />}
                body={<FrameBody>{content}</FrameBody>}
                footer={
                    <FrameFooter>
                        <Button onClick={promptCloseConfirmation} baseClass={ButtonTypes.DASHBOARD_STANDARD}>
                            Cancel
                        </Button>
                        <Button onClick={promptCloseConfirmation} baseClass={ButtonTypes.DASHBOARD_PRIMARY}>
                            Save
                        </Button>
                    </FrameFooter>
                }
            />
        );
    }

    return (
        <>
            <Button
                className={classes.translateIcon}
                baseClass={ButtonTypes.ICON}
                onClick={() => setDisplayModal(true)}
            >
                <TranslateIcon />
            </Button>
            {displayModal && (
                <Modal
                    titleID={titleID}
                    exitHandler={promptCloseConfirmation}
                    size={props.isFullScreen ? ModalSizes.FULL_SCREEN : ModalSizes.LARGE}
                    scrollable={props.isFullScreen}
                >
                    {content}
                </Modal>
            )}
            {showCloseConfirm && (
                <ModalConfirm title={t("Unsaved Changes")} onConfirm={closeSelf} onCancel={closeSelf}>
                    {t(
                        "You have unsaved changes and your work will be lost. Are you sure you want to continue without saving?",
                    )}
                </ModalConfirm>
            )}

            {showChangeConfirm && (
                <ModalConfirm
                    title={t("Unsaved Changes")}
                    onConfirm={() => {
                        setActiveLocale(showChangeConfirm);
                        setShowChangeConfirm(null);
                    }}
                    onCancel={() => {
                        setShowChangeConfirm(null);
                    }}
                >
                    {t(
                        "You have unsaved changes and your work will be lost. Are you sure you want to continue without saving?",
                    )}
                </ModalConfirm>
            )}
        </>
    );
};
