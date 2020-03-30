/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { KB_RESOURCE_NAME } from "@knowledge/constants";
import { useKnowledgeBaseActions } from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import { useKBData, useUniversalSources } from "@knowledge/knowledge-bases/knowledgeBaseHooks";
import { KnowledgeAddEditGeneral } from "@knowledge/knowledge-settings/addEdit/KnowledgeBaseAddEditGeneral";
import { KnowledgeBaseAddEditPermissions } from "@knowledge/knowledge-settings/addEdit/KnowledgeBaseAddEditPermissions";
import { knowledgeBaseAddEditClasses } from "@knowledge/knowledge-settings/addEdit/knowledgeBaseAddEditStyles";
import { KnowledgeBaseAddEditUniversal } from "@knowledge/knowledge-settings/addEdit/KnowledgeBaseAddEditUniversal";
import { LoadStatus } from "@library/@types/api/core";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import FrameHeader from "@library/layout/frame/FrameHeader";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { modalClasses } from "@library/modal/modalStyles";
import { useUniqueID } from "@library/utility/idUtils";
import { t, TranslationPropertyType, useContentTranslator } from "@vanilla/i18n";
import { Tabs } from "@vanilla/library/src/scripts/sectioning/Tabs";
import classNames from "classnames";
import React, { useEffect } from "react";
import ErrorMessages from "@vanilla/library/src/scripts/forms/ErrorMessages";
import flatten from "lodash/flatten";
import { notEmpty } from "@vanilla/utils";

interface IProps {
    kbID?: number;
    onClose: () => void;
}

export function KnowledgeBaseAddEdit(props: IProps) {
    const { form, formSubmit } = useKBData();
    const isLoading = formSubmit.status === LoadStatus.LOADING;
    const { saveKbForm, clearError, initForm } = useKnowledgeBaseActions();

    const isEditing = props.kbID != null;
    const isFormSubmitSuccessful = formSubmit.status === LoadStatus.SUCCESS;
    const sourceLocale = form.sourceLocale;

    const { kbID } = props;
    const universalSources = useUniversalSources(props.kbID);
    const universalTargetError =
        universalSources.length > 0 ? (
            <>
                <p>{t("This Knowledge Base is already displaying Universal Content from these Knowledge Bases:")}</p>
                <p>
                    {universalSources.map((kb, i) => (
                        <React.Fragment key={i}>
                            <strong>{kb.name}</strong>
                            {i < universalSources.length - 1 ? "," : null}
                        </React.Fragment>
                    ))}
                </p>
            </>
        ) : (
            undefined
        );
    useEffect(() => {
        initForm({ kbID });
    }, [kbID, initForm]);

    const onClose = () => {
        clearError();
        props.onClose();
    };

    useEffect(() => {
        if (isFormSubmitSuccessful) {
            onClose();
        }
    });

    const titleID = useUniqueID("addKnowledgeBase");

    const classFrameFooter = frameFooterClasses();

    const titleString = isEditing ? t("Edit Knowledge Base") : t("Add Knowledge Base");
    const { Translator, shouldDisplay } = useContentTranslator();
    const kbAddEditClasses = knowledgeBaseAddEditClasses();

    const allErrors = formSubmit.error?.response.data?.errors;

    const universalErrors = [allErrors?.["isUniversalSource"], allErrors?.["universalTargetIDs"]].filter(notEmpty);
    const permissionErrors = [
        allErrors?.["hasCustomPermissions"],
        allErrors?.["viewRoleIDs"],
        allErrors?.["editRoleIDs"],
    ].filter(notEmpty);
    const generalErrors = !allErrors
        ? []
        : Object.values(allErrors)
              .filter(error => {
                  return !universalErrors.includes(error) && !permissionErrors.includes(error);
              })
              .filter(notEmpty);

    return (
        <Modal isVisible={true} size={ModalSizes.XL} exitHandler={onClose} titleID={titleID}>
            <form
                className={modalClasses().frameWrapper}
                onSubmit={async event => {
                    event.preventDefault();
                    void saveKbForm();
                }}
            >
                <Frame
                    header={
                        <FrameHeader
                            titleID={titleID}
                            closeFrame={onClose}
                            title={titleString}
                            titleClass={kbAddEditClasses.heading}
                            borderless
                        >
                            {shouldDisplay && isEditing && (
                                <Translator
                                    resource={KB_RESOURCE_NAME}
                                    properties={[
                                        {
                                            recordType: "knowledgeBase",
                                            recordID: props.kbID,
                                            propertyName: "name",
                                            propertyType: TranslationPropertyType.TEXT,
                                            propertyValidation: {
                                                minLength: 1,
                                            },
                                            sourceText: form.name,
                                        },
                                        {
                                            recordType: "knowledgeBase",
                                            recordID: props.kbID,
                                            propertyName: "description",
                                            propertyType: TranslationPropertyType.TEXT_MULTILINE,
                                            propertyValidation: {
                                                minLength: 1,
                                            },
                                            sourceText: form.description,
                                        },
                                    ]}
                                    title={t("Translate Knowledge Base")}
                                    sourceLocale={sourceLocale}
                                ></Translator>
                            )}
                        </FrameHeader>
                    }
                    body={
                        <FrameBody selfPadded={true}>
                            <Tabs
                                data={[
                                    {
                                        label: "General",
                                        panelData: "",
                                        contents: <KnowledgeAddEditGeneral kbID={kbID} />,
                                        error:
                                            generalErrors.length > 0 ? (
                                                <ErrorMessages errors={flatten(generalErrors)} />
                                            ) : (
                                                undefined
                                            ),
                                    },
                                    {
                                        label: "Permissions",
                                        panelData: "",
                                        contents: <KnowledgeBaseAddEditPermissions kbID={kbID} />,
                                        error:
                                            permissionErrors.length > 0 ? (
                                                <ErrorMessages errors={flatten(permissionErrors).filter(notEmpty)} />
                                            ) : (
                                                undefined
                                            ),
                                    },
                                    {
                                        label: "Universal Content",
                                        panelData: "",
                                        contents: <KnowledgeBaseAddEditUniversal kbID={kbID} />,
                                        warning: universalTargetError,
                                        disabled: !!universalTargetError,
                                        error:
                                            universalErrors.length > 0 ? (
                                                <ErrorMessages errors={flatten(universalErrors).filter(notEmpty)} />
                                            ) : (
                                                undefined
                                            ),
                                    },
                                ]}
                            />
                        </FrameBody>
                    }
                    footer={
                        <FrameFooter justifyRight={true} forDashboard={true}>
                            <Button
                                className={classNames(classFrameFooter.actionButton)}
                                baseClass={ButtonTypes.DASHBOARD_SECONDARY}
                                onClick={onClose}
                                disabled={isLoading}
                            >
                                {t("Cancel")}
                            </Button>
                            <Button
                                submit={true}
                                className={classNames(classFrameFooter.actionButton)}
                                baseClass={ButtonTypes.DASHBOARD_PRIMARY}
                                disabled={isLoading}
                            >
                                {isLoading ? <ButtonLoader /> : t("Save")}
                            </Button>
                        </FrameFooter>
                    }
                />
            </form>
        </Modal>
    );
}
