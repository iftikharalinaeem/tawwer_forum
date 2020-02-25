/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { KB_RESOURCE_NAME } from "@knowledge/constants";
import { useKnowledgeBaseActions } from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import { useKBData } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { KnowledgeAddEditGeneral } from "@knowledge/knowledge-settings/addEdit/KnowledgeBaseAddEditGeneral";
import { KnowledgeBaseAddEditPermissions } from "@knowledge/knowledge-settings/addEdit/KnowledgeBaseAddEditPermissions";
import { knowledgeBaseAddEditClasses } from "@knowledge/knowledge-settings/addEdit/knowledgeBaseAddEditStyles";
import { KnowledgeBaseAddEditUniversal } from "@knowledge/knowledge-settings/addEdit/KnowledgeBaseAddEditUniversal";
import { LoadStatus } from "@library/@types/api/core";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
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
                                    },
                                    {
                                        label: "Permissions",
                                        panelData: "",
                                        contents: <KnowledgeBaseAddEditPermissions kbID={kbID} />,
                                    },
                                    {
                                        label: "Universal Content",
                                        panelData: "",
                                        contents: <KnowledgeBaseAddEditUniversal kbID={kbID} />,
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
