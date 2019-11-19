/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardFormList } from "@dashboard/forms/DashboardFormList";
import { DashboardImageUploadGroup } from "@dashboard/forms/DashboardImageUploadGroup";
import { DashboardInput } from "@dashboard/forms/DashboardInput";
import { DashboardRadioButton } from "@dashboard/forms/DashboardRadioButton";
import { DashboardRadioGroup } from "@dashboard/forms/DashboardRadioGroups";
import { DashboardSelect } from "@dashboard/forms/DashboardSelect";
import { useKnowledgeBaseActions } from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import { KbViewType, KnowledgeBaseSortMode, useKBData } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { LoadStatus, IFieldError } from "@library/@types/api/core";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { MenuPlacement } from "@library/forms/select/SelectOne";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import FrameHeader from "@library/layout/frame/FrameHeader";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { modalClasses } from "@library/modal/modalStyles";
import { getComponent } from "@library/utility/componentRegistry";
import { useUniqueID } from "@library/utility/idUtils";
import { t, useLocaleInfo, useContentTranslator, TranslationPropertyType, getCurrentLocale } from "@vanilla/i18n";
import classNames from "classnames";
import React, { useState, useEffect } from "react";
import Message from "@library/messages/Message";
import { KB_RESOURCE_NAME } from "@knowledge/constants";
import ErrorMessages from "@library/forms/ErrorMessages";
import { knowledgeBaseAddEditClasses } from "@knowledge/knowledge-settings/knowledgeBaseAddEditStyles";

interface IProps {
    kbID?: number;
    onClose: () => void;
}

export function KnowledgeBaseAddEdit(props: IProps) {
    const { form, formSubmit } = useKBData();
    const isLoading = formSubmit.status === LoadStatus.LOADING;
    const { updateForm, saveKbForm, clearError, initForm } = useKnowledgeBaseActions();
    const { locales } = useLocaleInfo();
    const localeOptions = Object.values(locales).map(locale => {
        return {
            value: locale.localeKey,
            label: locale.displayNames[locale.localeKey],
        };
    });

    const isEditing = props.kbID != null;
    const isFormSubmitSuccessful = formSubmit.status === LoadStatus.SUCCESS;

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

    const helpCenterSortOptions: IComboBoxOption[] = [
        {
            value: KnowledgeBaseSortMode.DATE_INSERTED_DESC,
            label: t("Newest First"),
        },
        {
            value: KnowledgeBaseSortMode.DATE_INSERTED,
            label: t("Oldest First"),
        },
        {
            value: KnowledgeBaseSortMode.NAME,
            label: t("Alphabetically"),
        },
    ];

    const ProductSelectorFormGroup = getComponent("ProductSelectorFormGroup");
    const titleString = isEditing ? t("Edit Knowledge Base") : t("Add Knowledge Base");
    const sourceLocale = form.sourceLocale;

    const { Translator, shouldDisplay } = useContentTranslator();

    const errors = formSubmit.error?.response.data?.errors;
    const kbAddEditClasses = knowledgeBaseAddEditClasses();

    return (
        <Modal size={ModalSizes.XL} exitHandler={onClose} titleID={titleID}>
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
                        <FrameBody>
                            <DashboardFormList>
                                <DashboardFormGroup label="Title" description={t("Title of the knowledge base.")}>
                                    <DashboardInput
                                        errors={errors?.["name"]}
                                        inputProps={{
                                            disabled: isLoading,
                                            onChange: (event: React.ChangeEvent<HTMLInputElement>) => {
                                                const { value } = event.target;
                                                updateForm({ name: value });
                                            },
                                            value: form.name,
                                        }}
                                    />
                                </DashboardFormGroup>
                                <DashboardFormGroup
                                    label="URL Code"
                                    description={t(
                                        "A customized version of the knowledge base name as it should appear in URLs.",
                                    )}
                                >
                                    <DashboardInput
                                        errors={errors?.["urlCode"]}
                                        inputProps={{
                                            disabled: isLoading,
                                            onChange: (event: React.ChangeEvent<HTMLInputElement>) => {
                                                const { value } = event.target;
                                                updateForm({ urlCode: value });
                                            },
                                            value: form.urlCode,
                                        }}
                                    />
                                </DashboardFormGroup>

                                {ProductSelectorFormGroup && (
                                    <ProductSelectorFormGroup.Component
                                        value={form.siteSectionGroup}
                                        onChange={siteSectionGroup => {
                                            updateForm({ siteSectionGroup });
                                        }}
                                        valueType={"sectionGroup"}
                                        disabled={isLoading}
                                    />
                                )}

                                <DashboardFormGroup
                                    label="Description"
                                    description={t(
                                        "A description of the knowledge base. Displayed in the knowledge base picker.",
                                    )}
                                >
                                    <DashboardInput
                                        inputProps={{
                                            disabled: isLoading,
                                            onChange: (event: React.ChangeEvent<HTMLInputElement>) => {
                                                const { value } = event.target;
                                                updateForm({ description: value });
                                            },
                                            value: form.description,
                                            multiline: true,
                                        }}
                                        multiLineProps={{
                                            rows: 6,
                                            maxRows: 6,
                                        }}
                                    />
                                </DashboardFormGroup>

                                <DashboardImageUploadGroup
                                    label="Icon"
                                    description={t(
                                        "A small image used to represent the knowledge base. Displayed in the knowledge base picker.",
                                    )}
                                    onChange={icon => {
                                        updateForm({ icon });
                                    }}
                                    disabled={isLoading}
                                    value={form.icon}
                                />
                                <DashboardImageUploadGroup
                                    label="Banner Image"
                                    description={t("Homepage banner image for this knowledge base.")}
                                    onChange={bannerImage => {
                                        updateForm({ bannerImage });
                                    }}
                                    disabled={isLoading}
                                    value={form.bannerImage}
                                />
                                <DashboardFormGroup
                                    label="View Type"
                                    description={t("Homepage banner image for this knowledge base.")}
                                >
                                    <DashboardRadioGroup
                                        value={form.viewType}
                                        onChange={viewType => {
                                            updateForm({ viewType: viewType as KbViewType });
                                        }}
                                    >
                                        <DashboardRadioButton
                                            label={"Guide"}
                                            note={t(
                                                'Guides are for making howto guides, documentation, or any "book" like content that should be read in order.',
                                            )}
                                            value={KbViewType.GUIDE}
                                            name={KbViewType.GUIDE}
                                            disabled={isLoading}
                                        />
                                        <DashboardRadioButton
                                            label={"Help Center"}
                                            note={t(
                                                "Help centers are for making free-form help articles that are organized into categories.",
                                            )}
                                            value={KbViewType.HELP}
                                            name={KbViewType.HELP}
                                            disabled={isLoading}
                                        />
                                    </DashboardRadioGroup>
                                </DashboardFormGroup>
                                {form.viewType === KbViewType.HELP && (
                                    <DashboardFormGroup
                                        label={t("Sort Articles")}
                                        description={t("Sorting method for articles.")}
                                    >
                                        <DashboardSelect
                                            isClearable={false}
                                            options={helpCenterSortOptions}
                                            menuPlacement={MenuPlacement.TOP}
                                            value={helpCenterSortOptions.find(option => {
                                                return option.value === form.sortArticles;
                                            })}
                                            onChange={option => {
                                                updateForm({
                                                    sortArticles: option!.value as KnowledgeBaseSortMode,
                                                });
                                            }}
                                        ></DashboardSelect>
                                    </DashboardFormGroup>
                                )}
                                <DashboardFormGroup
                                    label={"Locale"}
                                    description={"Determines how the categories and articles within it will display"}
                                >
                                    <DashboardSelect
                                        isClearable={false}
                                        options={localeOptions}
                                        disabled={isLoading}
                                        menuPlacement={MenuPlacement.TOP}
                                        onChange={(option: IComboBoxOption | null) => {
                                            updateForm({ sourceLocale: option ? option.value.toString() : null });
                                        }}
                                        value={localeOptions.find(val => {
                                            return val.value == form.sourceLocale;
                                        })}
                                    />
                                </DashboardFormGroup>
                            </DashboardFormList>
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
