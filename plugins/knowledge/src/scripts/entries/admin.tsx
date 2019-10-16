/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { onContent, onReady, t } from "@library/utility/appUtils";
import { KbViewType } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { mountModal } from "@library/modal/Modal";
import React from "react";
import { ConfirmLocaleChange } from "@knowledge/entries/ConfirmLocaleChange";

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
