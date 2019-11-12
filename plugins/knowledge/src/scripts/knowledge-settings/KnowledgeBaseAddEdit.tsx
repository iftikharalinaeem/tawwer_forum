/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { ButtonTypes } from "@library/forms/buttonStyles";
import { t } from "@vanilla/i18n";
import React, { useMemo, useState, useCallback } from "react";
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
import { useProducts } from "@subcommunities/products/productSelectors";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import Translate from "@library/content/Translate";
import { DashboardFormList } from "@dashboard/forms/DashboardFormList";
import { DashboardInput } from "@dashboard/forms/DashboardInput";
import { DashboardImageUploadGroup } from "@dashboard/forms/DashboardImageUploadGroup";
import { DashboardRadioGroup } from "@dashboard/forms/DashboardRadioGroups";
import { DashboardRadioButton } from "@dashboard/forms/DashboardRadioButton";
import { ProductManager } from "@subcommunities/products/ProductManager";
import { LocaleDisplayer, useLocaleInfo } from "@vanilla/i18n";
import { match } from "react-router";
import apiv2 from "@library/apiv2";
import KnowledgeBaseActions, { useKnowledgeBaseActions } from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import { connect } from "react-redux";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import e from "express";
import { KbViewType, useKBData } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { IComboBoxOption } from "@library/features/search/SearchBar";

const doNothing = () => {
    return;
};

interface IKbForm {
    title: string;
    url: string;
    product: string;
    description: string;
    icon: string;
    image: string;
    viewType: KbViewType;
    locale: string;
}

export function KnowledgeBaseAddEdit(props: IProps) {
    const { form } = useKBData();
    const { updateForm, saveKbForm } = useKnowledgeBaseActions();
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [isProductManagementOpen, setIsProductManagementOpen] = useState(false);
    /* const kbObj: IKbForm = {
        title: "",
        url: "",
        product: "",
        description: "",
        icon: "",
        image: "",
        viewType: KbViewType.HELP,
        locale: "",
    };
    const [obj, setKbObj] = useState(kbObj);
    const setValue = (partial: Partial<IKbForm>) => {
        console.log("inside setVale");
        return setKbObj({
            ...obj,
            ...partial,
        });
    };*/

    const [isLoading, setIsLoading] = useState(false);
    const { locales, currentLocale } = useLocaleInfo();

    const localeOptions = Object.values(locales).map(locale => {
        return {
            value: locale.localeKey,
            label: locale.displayNames[locale.localeKey],
        };
    });

    const { allProductLoadable, productsById } = useProducts();
    const productOptions = useMemo(() => {
        return Object.values(productsById).map(productLoadable => {
            const { productID } = productLoadable.product;
            return {
                label: productLoadable.product.name,
                value: productID,
            };
        });
    }, [productsById]);

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

    const save = e => {
        // setIsFormOpen(false);
        console.log("values==>", obj);
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
            {(isFormOpen || true) && (
                <Modal
                    size={ModalSizes.XL}
                    exitHandler={onCancel}
                    titleID={titleID}
                    elementToFocusOnExit={toggleButtonRef.current as HTMLElement}
                    scrollable={true}
                >
                    <Frame
                        header={
                            <FrameHeader titleID={titleID} closeFrame={onClose} title={t("Add/Edit Knowledge Base")} />
                        }
                        body={
                            <FrameBody>
                                <DashboardFormList>
                                    <DashboardFormGroup label="Title" description={t("Title of the knowledge base.")}>
                                        <DashboardInput
                                            inputProps={{
                                                disabled: isLoading,
                                                onChange: (event: React.ChangeEvent<HTMLInputElement>) => {
                                                    const { value } = event.target;
                                                    updateForm({ title: value });
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
                                                    updateForm({ url: value });
                                                },
                                            }}
                                        />
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
                                                    >
                                                        {content}
                                                    </Button>
                                                )}
                                            />
                                        }
                                    >
                                        <DashboardSelect
                                            disabled={isLoading}
                                            options={productOptions}
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
                                            inputProps={{
                                                disabled: isLoading,
                                                onChange: (event: React.ChangeEvent<HTMLInputElement>) => {
                                                    const { value } = event.target;
                                                    updateForm({ description: value });
                                                },
                                                multiline: true,
                                            }}
                                        />
                                    </DashboardFormGroup>

                                    <DashboardImageUploadGroup
                                        label="Icon"
                                        description={t(
                                            "A small image used to represent the knowledge base. Displayed in the knowledge base picker.",
                                        )}
                                        onChange={doNothing}
                                        value={""}
                                    />
                                    <DashboardImageUploadGroup
                                        label="Banner Image"
                                        description={t("Homepage banner image for this knowledge base.")}
                                        onChange={doNothing}
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
                                                value={KbViewType.GUIDE}
                                                name={viewType}
                                            />
                                            <DashboardRadioButton
                                                label={"Help Center"}
                                                note={t(
                                                    "Help centers are for making free-form help articles that are organized into categories.",
                                                )}
                                                value={KbViewType.HELP}
                                                name={viewType}
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
                                        options={localeOptions}
                                        onChange={(options: IComboBoxOption[]) => {
                                            updateForm({ locale: options });
                                        }}
                                        value={form.locale}
                                    />
                                </DashboardFormGroup>
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter justifyRight={true}>
                                <Button
                                    className={classFrameFooter.actionButton}
                                    baseClass={ButtonTypes.TEXT}
                                    onClick={onCancel}
                                >
                                    {t("Cancel")}
                                </Button>
                                <Button
                                    className={classFrameFooter.actionButton}
                                    onClick={saveKb}
                                    baseClass={ButtonTypes.TEXT_PRIMARY}
                                    disabled={isLoading}
                                >
                                    {isLoading ? <ButtonLoader /> : t("Save")}
                                </Button>
                            </FrameFooter>
                        }
                    />
                </Modal>
            )}
            {isProductManagementOpen && (
                <ProductManager asModal={true} onClose={() => setIsProductManagementOpen(false)} />
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

const withRedux = connect(
    mapStateToProps,
    mapDispatchToProps,
);

export default withRedux(KnowledgeBaseAddEdit);
