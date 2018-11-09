/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { t } from "@library/application";
import Heading from "@library/components/Heading";
import InputTextBlock from "@library/components/forms/InputTextBlock";
import Tokens from "@library/components/forms/select/Tokens";
import { dummyAuthors } from "@knowledge/modules/search/state/dummyAuthors";
import Checkbox from "@library/components/forms/Checkbox";
import SelectOne from "@library/components/forms/select/SelectOne";
import { dummyKnowledgeBaseList } from "@knowledge/modules/search/state/dummyKnowledgeBaseList";
import Button from "@library/components/forms/Button";
import { connect } from "react-redux";
import SearchPageModel, { ISearchFormState, ISearchPageState } from "@knowledge/modules/search/SearchPageModel";
import SearchPageActions, { ISearchFormActionProps } from "@knowledge/modules/search/SearchPageActions";
import DateRange from "@knowledge/modules/search/components/DateRange";
import MultiUserInput from "@library/users/MultiUserInput";
import { IComboBoxOption } from "@library/components/forms/select/SearchBar";

export enum ISearchDomain {
    ARTICLES = "articles",
    EVERYWHERE = "everywhere",
}

export interface IProps extends ISearchFormActionProps, ISearchPageState {}

/**
 * Implements the search bar component
 */
export class AdvancedSearch extends React.Component<IProps> {
    public render() {
        const formData = this.props.form;

        return (
            <form className="advancedSearch" onSubmit={this.noop}>
                <Heading className="advancedSearch-title pageSubTitle">{t("Advanced Search")}</Heading>
                <InputTextBlock
                    label={t("Title")}
                    inputProps={{
                        onChange: this.handleTitleChange,
                        value: formData.title,
                    }}
                />
                <MultiUserInput onChange={this.handleUserChange} value={this.props.form.authors} />
                <DateRange
                    onStartChange={this.handleStartDateChange}
                    onEndChange={this.handleEndDateChange}
                    start={this.props.form.startDate}
                    end={this.props.form.endDate}
                />
                {dummyKnowledgeBaseList &&
                    dummyKnowledgeBaseList.length > 0 && (
                        <SelectOne
                            label={t("Knowledge Base")}
                            className="inputBlock dateRange-within"
                            options={dummyKnowledgeBaseList}
                            onChange={this.noop}
                        />
                    )}
                <Checkbox
                    label={t("Deleted Articles")}
                    onChange={this.handleCheckBoxDeletedArticleChange}
                    checked={formData.includeDeleted}
                    className="inputBlock"
                />
                <Button type="submit" className="advancedSearch-submit" prefix="submitButton">
                    {t("Search")}
                </Button>
            </form>
        );
    }

    /**
     * Handler for the deleted articles checkbox.
     */
    private handleCheckBoxDeletedArticleChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        console.log(event.target.checked);
        this.props.searchActions.updateForm({ includeDeleted: event.target.checked || false });
    };

    /**
     * Handler for title field.
     */
    private handleTitleChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const { value } = event.target;
        this.props.searchActions.updateForm({ title: value });
    };

    private handleStartDateChange = (date: string) => {
        this.props.searchActions.updateForm({ startDate: date });
    };

    private handleEndDateChange = (date: string) => {
        this.props.searchActions.updateForm({ endDate: date });
    };

    private handleUserChange = (options: IComboBoxOption[]) => {
        this.props.searchActions.updateForm({ authors: options });
    };

    private noop = () => {
        return;
    };
}

const withRedux = connect(
    SearchPageModel.mapStateToProps,
    SearchPageActions.mapDispatchToProps,
);

export default withRedux(AdvancedSearch);
