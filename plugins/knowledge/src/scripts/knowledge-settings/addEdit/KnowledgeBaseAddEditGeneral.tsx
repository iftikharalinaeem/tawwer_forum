/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardFormList } from "@dashboard/forms/DashboardFormList";
import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";
import { DashboardImageUploadGroup } from "@dashboard/forms/DashboardImageUploadGroup";
import { DashboardInput } from "@dashboard/forms/DashboardInput";
import { DashboardRadioButton } from "@dashboard/forms/DashboardRadioButton";
import { DashboardRadioGroup } from "@dashboard/forms/DashboardRadioGroups";
import { DashboardSelect } from "@dashboard/forms/DashboardSelect";
import { useKnowledgeBaseActions } from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import { KbViewType, KnowledgeBaseSortMode, useKBData } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { ConfirmLocaleChange } from "@knowledge/knowledge-settings/ConfirmLocaleChange";
import { LoadStatus } from "@library/@types/api/core";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { MenuPlacement } from "@library/forms/select/SelectOne";
import { getComponent } from "@library/utility/componentRegistry";
import { t, useLocaleInfo } from "@vanilla/i18n";
import { FrameBodyContainer } from "@vanilla/library/src/scripts/layout/frame/FrameBody";
import React, { useEffect, useState } from "react";
import Select from "react-select";

interface IProps {
    kbID?: number;
}

export function KnowledgeAddEditGeneral(props: IProps) {
    const localeSelectRef = React.createRef<Select>();
    const [localeToConfirm, setLocaleToConfirm] = useState<string | null>(null);
    const { form, formSubmit } = useKBData();
    const isLoading = formSubmit.status === LoadStatus.LOADING;
    const { updateForm, initForm } = useKnowledgeBaseActions();
    const { locales } = useLocaleInfo();
    const localeOptions = Object.values(locales).map(locale => {
        return {
            value: locale.localeKey,
            label: locale.displayNames[locale.localeKey],
        };
    });

    const isEditing = props.kbID != null;

    const { kbID } = props;
    useEffect(() => {
        initForm({ kbID });
    }, [kbID, initForm]);

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
    const errors = formSubmit.error?.response.data?.errors;

    return (
        <FrameBodyContainer>
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
                    label="Description"
                    description={t("A description of the knowledge base. Displayed in the knowledge base picker.")}
                >
                    <DashboardInput
                        errors={errors?.["description"]}
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
                <DashboardFormGroup
                    label="URL Code"
                    description={t("A customized version of the knowledge base name as it should appear in URLs.")}
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
                        errors={errors?.["siteSectionGroup"]}
                        value={form.siteSectionGroup}
                        onChange={siteSectionGroup => {
                            updateForm({ siteSectionGroup });
                        }}
                        valueType={"sectionGroup"}
                        disabled={isLoading}
                    />
                )}
                <DashboardFormGroup
                    label={"Locale"}
                    description={"Determines how the categories and articles within it will display."}
                >
                    <DashboardSelect
                        isClearable={false}
                        options={localeOptions}
                        disabled={isLoading}
                        menuPlacement={MenuPlacement.TOP}
                        selectRef={localeSelectRef}
                        onChange={(option: IComboBoxOption | null) => {
                            const sourceLocale = option ? option.value.toString() : null;
                            if (isEditing) {
                                // If we are editing an existing KB make sure we show a warning here.
                                // This is a potentially very confusing operation to mess up.
                                setLocaleToConfirm(sourceLocale);
                            } else {
                                updateForm({ sourceLocale });
                            }
                        }}
                        value={localeOptions.find(val => {
                            return val.value == form.sourceLocale;
                        })}
                    />
                </DashboardFormGroup>
                <ConfirmLocaleChange
                    isVisible={localeToConfirm !== null}
                    onConfirm={() => {
                        updateForm({ sourceLocale: localeToConfirm });
                        setLocaleToConfirm(null);
                        localeSelectRef.current?.focus();
                    }}
                    onCancel={() => {
                        setLocaleToConfirm(null);
                        localeSelectRef.current?.focus();
                    }}
                />
                <DashboardFormSubheading>{t("Images")}</DashboardFormSubheading>
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
                    label="Banner Background Image"
                    description={t("Background image for the banner in this Knowledge Base")}
                    onChange={bannerImage => {
                        updateForm({ bannerImage });
                    }}
                    disabled={isLoading}
                    value={form.bannerImage}
                />
                <DashboardImageUploadGroup
                    label="Banner Content Image"
                    description={t(
                        "An image to display alongside the title and the searchbar in the banner of this knowledge base.",
                    )}
                    onChange={bannerContentImage => {
                        updateForm({ bannerContentImage });
                    }}
                    disabled={isLoading}
                    value={form.bannerContentImage}
                />
                <DashboardFormSubheading>{t("Layout Options")}</DashboardFormSubheading>
                <DashboardFormGroup label="View Type" description={t("Homepage banner image for this knowledge base.")}>
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
                    <DashboardFormGroup label={t("Sort Articles")} description={t("Sorting method for articles.")}>
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
            </DashboardFormList>
        </FrameBodyContainer>
    );
}
