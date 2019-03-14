/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { onReady, onContent } from "@library/dom/appUtils";
import { KbViewType } from "@knowledge/knowledge-bases/KnowledgeBaseModel";

onReady(handleKBViewTypeChange);
onContent(handleKBViewTypeChange);

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
