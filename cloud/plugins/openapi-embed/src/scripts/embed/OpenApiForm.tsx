/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState, useEffect, useCallback } from "react";
import Modal from "@vanilla/library/src/scripts/modal/Modal";
import ModalSizes from "@vanilla/library/src/scripts/modal/ModalSizes";
import Frame from "@vanilla/library/src/scripts/layout/frame/Frame";
import FrameHeader from "@vanilla/library/src/scripts/layout/frame/FrameHeader";
import FrameBody from "@vanilla/library/src/scripts/layout/frame/FrameBody";
import FrameFooter from "@vanilla/library/src/scripts/layout/frame/FrameFooter";
import Button from "@vanilla/library/src/scripts/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import InputTextBlock from "@vanilla/library/src/scripts/forms/InputTextBlock";
import { OpenApiPreview } from "@openapi-embed/embed/OpenApiPreview";
import { frameFooterClasses } from "@vanilla/library/src/scripts/layout/frame/frameFooterStyles";
import { isAllowedUrl, t } from "@vanilla/library/src/scripts/utility/appUtils";
import { IOpenApiEmbedData } from "@openapi-embed/embed/OpenApiEmbed";
import { useUniqueID } from "@vanilla/library/src/scripts/utility/idUtils";
import { ISwaggerHeading } from "@vanilla/library/src/scripts/features/swagger/useSwaggerUI";
import axios from "axios";
import { LoadStatus, IApiError } from "@vanilla/library/src/scripts/@types/api/core";
import ButtonLoader from "@vanilla/library/src/scripts/loaders/ButtonLoader";

interface IProps {
    isVisible: boolean;
    data: Partial<IOpenApiEmbedData>;
    onDismiss: () => void;
    onSave: (data: IOpenApiEmbedData) => void;
}

export function OpenApiForm(props: IProps) {
    const [url, setUrl] = useState(props.data.url ?? "");
    const [headings, setHeadings] = useState<ISwaggerHeading[]>([]);
    const [showPreview, setShowPreview] = useState(false);

    const { actionButton } = frameFooterClasses();
    const titleID = useUniqueID("title");

    const spec = useRemoteSpec(url);

    const clearForm = () => {
        setShowPreview(false);
        setHeadings([]);
        setUrl("");
    };

    const handleSubmit = () => {
        if (spec.status !== LoadStatus.SUCCESS || !url) {
            return;
        }
        clearForm();
        props.onDismiss();
        props.onSave({ url, embedType: "openapi", headings, specJson: JSON.stringify(spec.data) });
    };

    const onDimiss = () => {
        clearForm();
        props.onDismiss();
    };

    return (
        <Modal isVisible={props.isVisible} size={ModalSizes.MEDIUM} titleID={titleID} exitHandler={onDimiss}>
            <Frame
                header={<FrameHeader titleID={titleID} closeFrame={onDimiss} title={"Configure OpenApi Spec"} />}
                body={
                    <FrameBody hasVerticalPadding>
                        <InputTextBlock
                            label={"Spec URL"}
                            errors={spec.error ? [spec.error] : undefined}
                            inputProps={{
                                placeholder: "https://petstore.swagger.io/v2/swagger.json",
                                value: url,
                                onChange: e => {
                                    setUrl(e.target.value);
                                },
                            }}
                        />
                    </FrameBody>
                }
                footer={
                    <FrameFooter justifyRight>
                        <Button
                            disabled={!isAllowedUrl(url)}
                            className={actionButton}
                            baseClass={ButtonTypes.TEXT_PRIMARY}
                            onClick={() => {
                                spec.request(() => setShowPreview(true));
                            }}
                        >
                            {spec.status === LoadStatus.LOADING ? <ButtonLoader /> : t("Validate")}
                        </Button>
                    </FrameFooter>
                }
            />
            <OpenApiPreview
                isVisible={showPreview}
                onLoadHeadings={setHeadings}
                spec={spec.data}
                onConfirm={handleSubmit}
                onDismiss={() => {
                    setShowPreview(false);
                }}
            />
        </Modal>
    );
}

/**
 * Fetch a remote openapi spec and return it.
 */
function useRemoteSpec(specUrl: string) {
    const [data, setData] = useState<object>();
    const [error, setError] = useState<IApiError>();
    const [status, setStatus] = useState(LoadStatus.PENDING);

    const request = useCallback(
        async (onSuccess: () => void) => {
            setStatus(LoadStatus.LOADING);
            try {
                const response = await axios.get(specUrl, {});
                setStatus(LoadStatus.SUCCESS);
                setError(undefined);
                setData(response.data);
                onSuccess?.();
            } catch (e) {
                setData(undefined);
                setStatus(LoadStatus.ERROR);

                if (e.request && !e.response) {
                    e.message =
                        "A network error occurred. This could be a CORS issue or a dropped internet connection.";
                }
                setError(e);
            }
        },
        [specUrl],
    );

    return {
        data,
        error,
        status,
        request,
    };
}