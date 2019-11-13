/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { ButtonTypes } from "@library/forms/buttonStyles";
import { useLocaleInfo, t } from "@vanilla/i18n";
import React, { useMemo, useState } from "react";
import Button from "@library/forms/Button";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import Frame from "@library/layout/frame/Frame";
import FrameHeader from "@library/layout/frame/FrameHeader";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { useUniqueID } from "@library/utility/idUtils";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import { DashboardSelect } from "@dashboard/forms/DashboardSelect";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardFormList } from "@dashboard/forms/DashboardFormList";
import { DashboardInput } from "@dashboard/forms/DashboardInput";
import { DashboardImageUploadGroup } from "@dashboard/forms/DashboardImageUploadGroup";
import { DashboardRadioGroup } from "@dashboard/forms/DashboardRadioGroups";
import { DashboardRadioButton } from "@dashboard/forms/DashboardRadioButton";
import classNames from "classnames";
import { getComponent } from "@library/utility/componentRegistry";
import KnowledgeBaseActions, { useKnowledgeBaseActions } from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import { connect } from "react-redux";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { KbViewType, useKBData } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { match } from "react-router";
import apiv2 from "@library/apiv2";

const doNothing = () => {
    return;
};

export function KnowledgeBaseAddEdit(props: IProps) {
    const { form } = useKBData();
    const { updateForm, saveKbForm } = useKnowledgeBaseActions();
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [isProductManagementOpen, setIsProductManagementOpen] = useState(false);

    const [isLoading, setIsLoading] = useState(false);
    const { locales, currentLocale } = useLocaleInfo();
    const [value, setValue] = useState<number | string | null>();
    const localeOptions = Object.values(locales).map(locale => {
        return {
            value: locale.localeKey,
            label: locale.displayNames[locale.localeKey],
        };
    });

    const toggleButtonRef = React.createRef<HTMLButtonElement>();

    const titleID = useUniqueID("addKnowledgeBase");

    const viewType = useUniqueID("knowledgeBaseViewType");

    const classFrameFooter = frameFooterClasses();

    const onCancel = () => {
        setIsFormOpen(false);
    };

    const onClose = () => {
        setIsFormOpen(false);
    };

    const ProductSelectorFormGroup = getComponent("ProductSelectorFormGroup");

    return (
        <>
            <Button
                buttonRef={toggleButtonRef}
                baseClass={ButtonTypes.DASHBOARD_PRIMARY}
                onClick={() => setIsFormOpen(true)}
            >
                {t("Add Knowledge Base")}
            </Button>
            {isFormOpen && (
                <Modal
                    size={ModalSizes.XL}
                    exitHandler={onCancel}
                    titleID={titleID}
                    elementToFocusOnExit={toggleButtonRef.current as HTMLElement}
                >
                    <form
                        onSubmit={event => {
                            event.preventDefault();
                            return saveKbForm();
                        }}
                    >
                        <Frame
                            canGrow={true}
                            header={
                                <FrameHeader
                                    titleID={titleID}
                                    closeFrame={onClose}
                                    title={t("Add/Edit Knowledge Base")}
                                />
                            }
                            body={
                                <FrameBody>
                                    <DashboardFormList>
                                        <DashboardFormGroup
                                            label="Title"
                                            description={t("Title of the knowledge base.")}
                                        >
                                            <DashboardInput
                                                inputProps={{
                                                    disabled: isLoading,
                                                    onChange: (event: React.ChangeEvent<HTMLInputElement>) => {
                                                        const { value } = event.target;
                                                        updateForm({ name: value });
                                                    },
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
                                                inputProps={{
                                                    disabled: isLoading,
                                                    onChange: (event: React.ChangeEvent<HTMLInputElement>) => {
                                                        const { value } = event.target;
                                                        updateForm({ urlCode: value });
                                                    },
                                                }}
                                            />
                                        </DashboardFormGroup>

                                        {ProductSelectorFormGroup && (
                                            <ProductSelectorFormGroup.Component
                                                formFieldName={""}
                                                initialValue={null}
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
                                            onChange={doNothing}
                                            disabled={isLoading}
                                            value={""}
                                        />
                                        <DashboardImageUploadGroup
                                            label="Banner Image"
                                            description={t("Homepage banner image for this knowledge base.")}
                                            onChange={doNothing}
                                            disabled={isLoading}
                                            value={""}
                                        />
                                        <DashboardFormGroup
                                            label="View Type"
                                            description={t("Homepage banner image for this knowledge base.")}
                                        >
                                            <DashboardRadioGroup>
                                                <DashboardRadioButton
                                                    label={"Guide"}
                                                    note={t(
                                                        'Guides are for making howto guides, documentation, or any "book" like content that should be read in order.',
                                                    )}
                                                    value={form.viewType}
                                                    name={KbViewType.GUIDE}
                                                    disabled={isLoading}
                                                />
                                                <DashboardRadioButton
                                                    label={"Help Center"}
                                                    note={t(
                                                        "Help centers are for making free-form help articles that are organized into categories.",
                                                    )}
                                                    value={form.viewType}
                                                    name={KbViewType.HELP}
                                                    disabled={isLoading}
                                                />
                                            </DashboardRadioGroup>
                                        </DashboardFormGroup>
                                    </DashboardFormList>

                                    <DashboardFormGroup
                                        label="Locales"
                                        description={
                                            "Determines how the categories and articles within it will display"
                                        }
                                    >
                                        <DashboardSelect
                                            options={localeOptions}
                                            disabled={isLoading}
                                            onChange={(options: IComboBoxOption) => {
                                                updateForm({ locale: options.value.toString() });
                                            }}
                                            value={localeOptions.find(val => {
                                                return val.label == form.locale;
                                            })}
                                        />
                                    </DashboardFormGroup>
                                </FrameBody>
                            }
                            footer={
                                <FrameFooter justifyRight={true} forDashboard={true}>
                                    <Button
                                        className={classNames(classFrameFooter.actionButton)}
                                        baseClass={ButtonTypes.DASHBOARD_SECONDARY}
                                        onClick={onCancel}
                                        disabled={isLoading}
                                    >
                                        {t("Cancel")}
                                    </Button>
                                    <Button
                                        submit={true}
                                        className={classNames(classFrameFooter.actionButton)}
                                        //onClick={save}
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
            )}
        </>
    );
}
interface IOwnProps {
    match: match<{
        id: string;
    }>;
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;
function mapStateToProps(state: IKnowledgeAppStoreState, ownProps: IOwnProps) {
    const { knowledgeBasesByID } = state.knowledge.knowledgeBases;
    const kbID = parseInt(ownProps.match.params.id, 10);

    const knowledgeBase = {
        ...knowledgeBasesByID,
        data: knowledgeBasesByID.data ? knowledgeBasesByID.data[kbID] : undefined,
    };

    const hasError = !!state.knowledge.navigation.currentError;

    return {
        knowledgeBase,
        hasError,
    };
}
function mapDispatchToProps(dispatch) {
    const actions = new KnowledgeBaseActions(dispatch, apiv2);

    return { actions };
}

const withRedux = connect(mapStateToProps, mapDispatchToProps);

export default withRedux(KnowledgeBaseAddEdit);
