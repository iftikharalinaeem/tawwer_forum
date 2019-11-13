/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { TranslateIcon } from "@library/icons/common";
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
import React, { useState, useEffect, useMemo, useDebugValue } from "react";
import { TranslationGrid, ITranslations } from "../translationGrid/TranslationGrid";
import { contentTranslatorClasses } from "./contentTranslatorStyles";
import { ContentTranslaterFullHeader } from "./ContentTranslatorFullHeader";
import { useTranslationActions, useTranslationData, validateProperties } from "./translationHooks";
import { LoadStatus } from "@library/@types/api/core";
import { makeTranslationKey } from "./TranslationActions";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { useThrowError } from "@vanilla/react-utils";
import Permission from "@library/features/users/Permission";

/**
 * Constant to represent "no existing translations".
 * This ensure we don't cause unnecessray re-renders by dynamically recreating an object as a prop.
 */
const EMPTY_TRANSLATIONS: ITranslations = {};

/**
 * ContentTranslator implementation for ContentTranslationProvider.
 */
export const ContentTranslator = (props: IContentTranslatorProps) => {
    let [displayModal, setDisplayModal] = useState(false);
    const { currentLocale } = useLocaleInfo();
    const titleID = useUniqueID("translateCategoriesTitle");
    let [showCloseConfirm, setShowCloseConfirm] = useState(false);
    let [showChangeConfirm, setShowChangeConfirm] = useState<string | null>(null);

    const { translationsByLocale, formTranslations, submitLoadable, translationLocale } = useTranslationData();
    const { publishForm, init, updateForm } = useTranslationActions();
    const hasUnsavedChanges = Object.entries(formTranslations).length > 0;

    const currentTranslations = useCurrentTranslations();
    // Show the loader if we have a loader & don't have data yet.
    // We don't want show the loader unless we have absolutely nothing to show.
    const showLoader =
        (props.isLoading || currentTranslations.status === LoadStatus.LOADING) && !currentTranslations.data;
    const isSubmitLoading = submitLoadable.status === LoadStatus.LOADING;

    const { properties } = props;

    useEffect(() => {
        validateProperties(properties);
    }, [properties]);

    useInitSync(props);
    const existingTranslations = useExistingTranslations(props, !displayModal);

    const closeSelf = () => {
        setShowCloseConfirm(false);
        setDisplayModal(false);
    };

    const promptCloseConfirmation = () => {
        if (hasUnsavedChanges) {
            setShowCloseConfirm(true);
        } else {
            closeSelf();
        }
    };

    const setLocale = (locale: string) => {
        init({ resource: props.resource, translationLocale: locale });
    };

    if (!currentLocale) {
        return null;
    }

    const classes = contentTranslatorClasses();

    let content = showLoader ? (
        <Loader size={200} />
    ) : (
        <TranslationGrid
            key={translationLocale || undefined}
            properties={props.properties}
            activeLocale={translationLocale}
            onActiveLocaleChange={locale => {
                if (hasUnsavedChanges) {
                    setShowChangeConfirm(locale);
                } else {
                    setLocale(locale!);
                }
            }}
            onTranslationUpdate={updateForm}
            sourceLocale={currentLocale}
            existingTranslations={existingTranslations}
        />
    );

    if (props.isFullScreen) {
        content = (
            <>
                <ContentTranslaterFullHeader onBack={promptCloseConfirmation} isSubmitLoading={isSubmitLoading} />
                <div className={classes.content}>
                    <DocumentTitle title={props.title}>
                        <Heading
                            id={titleID}
                            className={classes.title}
                            depth={1}
                            renderAsDepth={2}
                            title={props.title}
                        />
                        id={titleID}
                        className={classes.title}
                        depth={1}
                        renderAsDepth={2}
                        title={props.title}
                        />
                    </DocumentTitle>
                    {content}
                </div>
            </>
        );
    } else {
        content = (
            <Frame
                header={<FrameHeader titleID={titleID} title={props.title} closeFrame={promptCloseConfirmation} />}
                body={<FrameBody>{content}</FrameBody>}
                footer={
                    <FrameFooter justifyRight={true} forDashboard={true}>
                        <Button onClick={promptCloseConfirmation} baseClass={ButtonTypes.DASHBOARD_SECONDARY}>
                            {t("Cancel")}
                        </Button>
                        <Button submit baseClass={ButtonTypes.DASHBOARD_PRIMARY}>
                            {isSubmitLoading ? <ButtonLoader buttonType={ButtonTypes.PRIMARY} /> : t("Save")}
                        </Button>
                    </FrameFooter>
                }
            />
        );
    }

    return (
        <Permission permission="settings.manage">
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
                    <form
                        onSubmit={e => {
                            e.preventDefault();
                            e.stopPropagation();
                            if (!translationLocale) {
                                return;
                            }
                            publishForm(props.properties);
                        }}
                    >
                        {content}
                    </form>
                </Modal>
            )}
            {showCloseConfirm && (
                <ModalConfirm
                    title={t("Unsaved Changes")}
                    onConfirm={closeSelf}
                    onCancel={() => setShowCloseConfirm(false)}
                >
                    {t(
                        "You have unsaved changes and your work will be lost. Are you sure you want to continue without saving?",
                    )}
                </ModalConfirm>
            )}

            {showChangeConfirm && (
                <ModalConfirm
                    title={t("Unsaved Changes")}
                    onConfirm={() => {
                        setLocale(showChangeConfirm!);
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
        </Permission>
    );
};

function useCurrentTranslations() {
    const { translationsByLocale, translationLocale } = useTranslationData();

    return translationLocale && translationsByLocale[translationLocale]
        ? translationsByLocale[translationLocale]
        : { status: LoadStatus.PENDING };
}

function useExistingTranslations(props: IContentTranslatorProps, ignoreFetch: boolean) {
    const { properties } = props;
    const { translationLocale, resource } = useTranslationData();
    const currentTranslations = useCurrentTranslations();
    const { getTranslationsForProperties } = useTranslationActions();
    const translationData = currentTranslations.data;
    const translationStatus = currentTranslations.status;

    useEffect(() => {
        if (!translationLocale || ignoreFetch || translationStatus !== LoadStatus.PENDING) {
            return;
        }
        getTranslationsForProperties(properties);
    }, [translationLocale, translationStatus, resource, ignoreFetch, getTranslationsForProperties, properties]);

    const result = useMemo(() => {
        if (!translationData) {
            return EMPTY_TRANSLATIONS;
        } else {
            const result: ITranslations = {};
            for (const translation of Object.values(translationData)) {
                result[makeTranslationKey(translation)] = translation.translation;
            }
            return result;
        }
    }, [translationData]);

    useDebugValue({ existingTranslations: result });
    return result;
}

function useFirstNonSourceLocale() {
    const { locales, currentLocale } = useLocaleInfo();
    const thrower = useThrowError();
    const filtered = locales.filter(locale => locale.localeKey !== currentLocale);
    if (filtered.length === 0) {
        thrower(new Error("<ContentTranslator /> should not be instantiated w/ only 1 locale"));
    }

    const result = filtered[0].localeKey;
    useDebugValue({
        firstNonSourceLocale: result,
    });
    return result;
}

function useInitSync(props: IContentTranslatorProps) {
    const { init } = useTranslationActions();
    const firstLocale = useFirstNonSourceLocale();
    const { resource } = props;
    useEffect(() => {
        init({
            translationLocale: firstLocale,
            resource,
        });
    }, [firstLocale, resource, init]);
}
