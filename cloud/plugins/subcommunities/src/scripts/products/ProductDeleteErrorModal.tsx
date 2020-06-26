/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
import React, { useRef } from "react";
import { IProductDeleteError, IProduct } from "@subcommunities/products/productTypes";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { useUniqueID } from "@library/utility/idUtils";
import Frame from "@library/layout/frame/Frame";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import FrameBody from "@library/layout/frame/FrameBody";
import SmartAlign from "@library/layout/SmartAlign";
import FrameFooter from "@library/layout/frame/FrameFooter";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import Translate from "@library/content/Translate";
import { useSubcommunities } from "@subcommunities/subcommunities/subcommunitySelectors";
import UserContent from "@library/content/UserContent";
import { userContentClasses } from "@library/content/userContentStyles";
import { SubcommunityList } from "@subcommunities/subcommunities/SubcommunityList";
import SmartLink from "@library/routing/links/SmartLink";

interface IProps {
    errorData: IProductDeleteError;
    onClose: () => void;
    elementToFocusOnExit: HTMLElement | null;
    product: IProduct;
    isVisible: boolean;
}

export function ProductDeleteErrorModal(props: IProps) {
    const titleID = useUniqueID("deleteModalTitle");
    const cancelRef = useRef<HTMLButtonElement>();

    const classesFrameBody = frameBodyClasses();
    const classFrameFooter = frameFooterClasses();
    return (
        <Modal
            isVisible={props.isVisible}
            size={ModalSizes.SMALL}
            elementToFocus={cancelRef.current}
            exitHandler={props.onClose}
            titleID={titleID}
            elementToFocusOnExit={props.elementToFocusOnExit!}
        >
            <Frame
                header={<FrameHeader titleID={titleID} closeFrame={props.onClose} title={t("Delete Product")} />}
                body={
                    <FrameBody className={userContentClasses().root}>
                        <div className={classNames(classesFrameBody.contents, userContentClasses().root)}>
                            <p>
                                <Translate
                                    source="You can't delete the product <0 /> because it's associated with existing subcommunities."
                                    c0={<strong>{props.product.name}</strong>}
                                />
                            </p>
                            <SubcommunityList subcommunityIDs={props.errorData.subcommunityIDs} />
                            <p>
                                <Translate
                                    source="If you want to delete it you will have to unlink it from these subcommunities on the <0>Subcommunities Page</0>."
                                    c0={content => <SmartLink to="/subcommunities">{content}</SmartLink>}
                                />
                            </p>
                        </div>
                    </FrameBody>
                }
                footer={
                    <FrameFooter justifyRight={true}>
                        <Button
                            className={classFrameFooter.actionButton}
                            onClick={props.onClose}
                            baseClass={ButtonTypes.TEXT_PRIMARY}
                        >
                            {t("Dismiss")}
                        </Button>
                    </FrameFooter>
                }
            />
        </Modal>
    );
}
