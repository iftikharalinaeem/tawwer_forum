/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useSearchPageActions } from "@knowledge/modules/search/SearchPageActions";
import { SearchDomain, useSearchPageData } from "@knowledge/modules/search/searchPageReducer";
import { LoadStatus } from "@library/@types/api/core";
import { t } from "@library/utility/appUtils";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Button from "@library/forms/Button";
import Checkbox from "@library/forms/Checkbox";
import InputTextBlock from "@library/forms/InputTextBlock";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import Heading from "@library/layout/Heading";
import MultiUserInput from "@library/features/users/MultiUserInput";
import Permission from "@library/features/users/Permission";
import * as React from "react";
import classNames from "classnames";
import { buttonClasses } from "@library/forms/buttonStyles";
import { dateRangeClasses } from "@library/forms/dateRangeStyles";
import DateRange from "@library/forms/DateRange";
import KnowledgeBaseInput from "@knowledge/knowledge-bases/KnowledgeBaseInput";
import { typographyClasses } from "@library/styles/typographyStyles";
import CommunityCategoryInput from "@vanilla/addon-vanilla/forms/CommunityCategoryInput";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import { searchFormFilterClasses } from "@knowledge/modules/search/searchFormFilterStyles";
import { useSearchFilters } from "@library/contexts/SearchFilterContext";

interface IProps {
    hideTitle?: boolean;
}

/**
 * Implements the search bar component
 */
export function SearchFormFilters(props: IProps) {
    const { form, results } = useSearchPageData();
    const { updateForm, search } = useSearchPageActions();
    const buttons = buttonClasses();
    const classesDateRange = dateRangeClasses();
    const classes = searchFormFilterClasses();
    const classesTypography = typographyClasses();
    const classesInputBlock = inputBlockClasses();

    const { getFilterComponentsForDomain } = useSearchFilters();

    return (
        <form
            className={classes.root}
            onSubmit={event => {
                event.preventDefault();
                return search();
            }}
        >
            {!props.hideTitle && (
                <Heading
                    className={classNames(classes.title, classesTypography.subTitle)}
                    title={t("Filter Results")}
                />
            )}
            <InputTextBlock
                label={t("Title")}
                inputProps={{
                    onChange: (event: React.ChangeEvent<HTMLInputElement>) => {
                        const { value } = event.target;
                        updateForm({ title: value });
                    },
                    value: form.title,
                }}
            />
            <MultiUserInput
                className={classesInputBlock.root}
                onChange={(options: IComboBoxOption[]) => {
                    updateForm({ authors: options });
                }}
                value={form.authors}
            />
            <DateRange
                onStartChange={(date: string) => {
                    console.log(date);
                    updateForm({ startDate: date });
                }}
                onEndChange={(date: string) => {
                    console.log(date);
                    updateForm({ endDate: date });
                }}
                start={form.startDate}
                end={form.endDate}
                className={classesDateRange.root}
            />
            {getFilterComponentsForDomain(form.domain)}
            {form.domain === SearchDomain.EVERYWHERE && (
                <CommunityCategoryInput
                    className="inputBlock"
                    onChange={(option: IComboBoxOption) => {
                        updateForm({ communityCategory: option });
                    }}
                    value={form.communityCategory}
                />
            )}
            {form.domain === SearchDomain.ARTICLES && (
                <KnowledgeBaseInput
                    className={classesInputBlock.root}
                    onChange={(option: IComboBoxOption) => {
                        updateForm({ kb: option });
                    }}
                    value={form.kb}
                />
            )}

            <Permission permission="articles.add">
                <Checkbox
                    label={t("Deleted Articles")}
                    onChange={(event: React.ChangeEvent<HTMLInputElement>) => {
                        updateForm({ includeDeleted: event.target.checked || false });
                    }}
                    checked={form.includeDeleted}
                    className={classesInputBlock.root}
                />
            </Permission>
            <Button submit={true} className={classNames(classes.submit, buttons.standard)} prefix="submitButton">
                {results.status === LoadStatus.LOADING ? <ButtonLoader /> : t("Filter")}
            </Button>
        </form>
    );
}
