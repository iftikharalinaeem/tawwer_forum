/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { onReady, onContent } from "@library/application";

onReady(handleKBViewTypeChange);
onContent(handleKBViewTypeChange);

function handleKBViewTypeChange() {
    const viewTypeSelect = document.getElementById("Form_viewType") as HTMLSelectElement;
    if (viewTypeSelect) {
        updateSortArticles(viewTypeSelect.value);
        viewTypeSelect.addEventListener("change", () => {
            updateSortArticles(viewTypeSelect.value);
        });
    }
}

function updateSortArticles(viewType: string) {
    const sortArticlesGroup = document.querySelectorAll(".js-sortArticlesGroup");
    console.log(sortArticlesGroup);
    if (viewType === "help") {
        sortArticlesGroup.forEach(group => {
            group.classList.remove("Hidden");
        });
    } else {
        sortArticlesGroup.forEach(group => {
            group.classList.add("Hidden");
        });
    }
}
