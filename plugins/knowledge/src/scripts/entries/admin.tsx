/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { onContent, onReady, t } from "@library/utility/appUtils";
import { KbViewType } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { mountModal } from "@library/modal/Modal";
import ModalConfirm from "@library/modal/ModalConfirm";
import React, { useState } from "react";
import Paragraph from "@library/layout/Paragraph";
import Translate from "@library/content/Translate";
import ModalSizes from "@library/modal/ModalSizes";
import { ITranslationGrid } from "@library/content/translationGrid/TranslationGrid";

onReady(handleKBViewTypeChange);
onContent(() => {
    handleKBViewTypeChange();
    handleKBSourceLocaleChange();
});

const hiddenClass = "Hidden";

function handleKBViewTypeChange() {
    const viewTypeSelect = document.querySelectorAll("input.js-viewType");

    if (viewTypeSelect.length) {
        viewTypeSelect.forEach((input: HTMLInputElement) => {
            updateSortArticles();
            input.addEventListener("change", () => updateSortArticles());
        });
    }
}

function updateSortArticles() {
    const checkedType: HTMLInputElement | null = document.querySelector("input.js-viewType:checked");
    const sortGroup: HTMLElement | null = document.querySelector("li.js-sortArticlesGroup");

    if (checkedType === null || sortGroup === null) {
        return;
    }

    switch (checkedType.value) {
        case KbViewType.GUIDE:
            sortGroup.classList.add(hiddenClass);
            break;
        default:
            sortGroup.classList.remove(hiddenClass);
    }
}

function ConfirmLocaleChange(props: { oldValue: string; target: HTMLSelectElement }) {
    const { oldValue, target } = props;
    const [showModal, setShowModal] = useState(true);

    return showModal ? (
        <ModalConfirm
            title={t("Are you sure?")}
            onCancel={() => {
                target.value = oldValue;
            }}
            onConfirm={() => {
                setShowModal(false);
            }}
            elementToFocusOnExit={target}
            size={ModalSizes.SMALL}
        >
            <Paragraph>
                <Translate
                    source="Changing your source locale can lead to articles disappearing and is not recommended. Are you sure you want to change the source locale? <0>More information</0>."
                    c0={content => {
                        return (
                            <a
                                href="https://success.vanillaforums.com/kb/articles/118"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                {content}
                            </a>
                        );
                    }}
                />
            </Paragraph>
        </ModalConfirm>
    ) : null;
}

function handleKBSourceLocaleChange() {
    const sourceLocaleChange = document.querySelectorAll("select[data-sourceLocale-kb]");
    if (sourceLocaleChange.length) {
        sourceLocaleChange.forEach((select: HTMLSelectElement) => {
            select.addEventListener("change", event => {
                const target = event.target as HTMLSelectElement;
                const newValue = target.value ? target.value : "";
                const oldValue = select.getAttribute("data-sourceLocale-kb");
                let showModal = true;
                if (!!oldValue && newValue !== oldValue) {
                    mountModal(<ConfirmLocaleChange oldValue={oldValue} target={target} />);
                }
            });
        });
    }
}
