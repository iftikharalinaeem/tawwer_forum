/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { ButtonTypes } from "@library/forms/buttonStyles";
import { t } from "@vanilla/i18n";
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
import Translate from "@library/content/Translate";
import { DashboardFormList } from "@dashboard/forms/DashboardFormList";
import { DashboardInput } from "@dashboard/forms/DashboardInput";
import { DashboardImageUploadGroup } from "@dashboard/forms/DashboardImageUploadGroup";
import { DashboardRadioGroup } from "@dashboard/forms/DashboardRadioGroups";
import { DashboardRadioButton } from "@dashboard/forms/DashboardRadioButton";
import classNames from "classnames";

interface IProps {}

const doNothing = () => {
    return;
};

export function KnowledgeBaseAddEdit(props: IProps) {
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [isProductManagementOpen, setIsProductManagementOpen] = useState(false);
    const [isLoading, setIsLoading] = useState(false);

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

    const save = () => {
        setIsFormOpen(false);
    };

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
                    <Frame
                        canGrow={true}
                        header={
                            <FrameHeader titleID={titleID} closeFrame={onClose} title={t("Add/Edit Knowledge Base")} />
                        }
                        body={
                            <FrameBody>
                                <DashboardFormList>
                                    <DashboardFormGroup label="Title" description={t("Title of the knowledge base.")}>
                                        <DashboardInput inputProps={{ disabled: isLoading, onChange: doNothing }} />
                                    </DashboardFormGroup>

                                    <DashboardFormGroup
                                        label="URL Code"
                                        description={t(
                                            "A customized version of the knowledge base name as it should appear in URLs.",
                                        )}
                                    >
                                        <DashboardInput inputProps={{ disabled: isLoading, onChange: doNothing }} />
                                    </DashboardFormGroup>

                                    <DashboardFormGroup
                                        label="Product"
                                        description={
                                            <Translate
                                                source="Associate a product with this Subcommunity. <0>Use the product management UI</0> to replace add, edit, or delete products."
                                                c0={content => (
                                                    <Button
                                                        baseClass={ButtonTypes.TEXT_PRIMARY}
                                                        onClick={event => {
                                                            setIsProductManagementOpen(true);
                                                        }}
                                                        disabled={isLoading}
                                                    >
                                                        {content}
                                                    </Button>
                                                )}
                                            />
                                        }
                                    >
                                        <DashboardSelect
                                            disabled={isLoading}
                                            options={[
                                                {
                                                    value: "en",
                                                    label: "English",
                                                },
                                                {
                                                    value: "en",
                                                    label: "English1",
                                                },
                                                {
                                                    value: "en",
                                                    label: "English2",
                                                },
                                            ]}
                                            onChange={doNothing}
                                        />
                                    </DashboardFormGroup>

                                    <DashboardFormGroup
                                        label="Description"
                                        description={t(
                                            "A description of the knowledge base. Displayed in the knowledge base picker.",
                                        )}
                                    >
                                        <DashboardInput
                                            inputProps={{ disabled: isLoading, onChange: doNothing, multiline: true }}
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
                                                value={""}
                                                name={viewType}
                                                disabled={isLoading}
                                            />
                                            <DashboardRadioButton
                                                label={"Help Center"}
                                                note={t(
                                                    "Help centers are for making free-form help articles that are organized into categories.",
                                                )}
                                                value={""}
                                                name={viewType}
                                                disabled={isLoading}
                                            />
                                        </DashboardRadioGroup>
                                    </DashboardFormGroup>
                                </DashboardFormList>

                                <DashboardFormGroup
                                    label="Locales"
                                    description={"Determines how the categories and articles within it will display"}
                                >
                                    <DashboardSelect
                                        disabled={isLoading}
                                        options={[
                                            {
                                                value: "en",
                                                label: "English",
                                            },
                                            {
                                                value: "en",
                                                label: "English1",
                                            },
                                            {
                                                value: "en",
                                                label: "English2",
                                            },
                                        ]}
                                        onChange={doNothing}
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
                                    className={classNames(classFrameFooter.actionButton)}
                                    onClick={save}
                                    baseClass={ButtonTypes.DASHBOARD_PRIMARY}
                                    disabled={isLoading}
                                >
                                    {isLoading ? <ButtonLoader /> : t("Save")}
                                </Button>
                            </FrameFooter>
                        }
                    />
                </Modal>
            )}
            {isProductManagementOpen &&
                {
                    /*<ProductManager asModal={true} onClose={() => setIsProductManagementOpen(false)} />*/
                }}
        </>
    );
}
