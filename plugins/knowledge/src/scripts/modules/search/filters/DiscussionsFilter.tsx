import React from "react";
import InputTextBlock from "@vanilla/library/src/scripts/forms/InputTextBlock";
import { t } from "@vanilla/library/src/scripts/utility/appUtils";
import { useUnifySearchPageActions } from "@knowledge/modules/search/UnifySearchPageActions";
import { useSearchPageData } from "@knowledge/modules/search/unifySearchPageReducer";
import Button from "@vanilla/library/src/scripts/forms/Button";
import classNames from "classnames";
import { searchFormFilterClasses } from "@knowledge/modules/search/searchFormFilterStyles";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import ButtonLoader from "@vanilla/library/src/scripts/loaders/ButtonLoader";
import { buttonClasses } from "@vanilla/library/src/scripts/forms/buttonStyles";
import MultiUserInput from "@vanilla/library/src/scripts/features/users/MultiUserInput";
import { IComboBoxOption } from "@vanilla/library/src/scripts/features/search/SearchBar";
import { inputBlockClasses } from "@vanilla/library/src/scripts/forms/InputBlockStyles";
import DateRange from "@vanilla/library/src/scripts/forms/DateRange";
import { dateRangeClasses } from "@vanilla/library/src/scripts/forms/dateRangeStyles";

export default function DiscussionFilter() {
    const { updateUnifyForm, unifySearch } = useUnifySearchPageActions();
    const { form, results } = useSearchPageData();
    console.log(form);
    const classes = searchFormFilterClasses();
    const buttons = buttonClasses();
    const classesInputBlock = inputBlockClasses();
    const classesDateRange = dateRangeClasses();

    return (
        <form>
            <InputTextBlock
                label={t("Title")}
                inputProps={{
                    onChange: (event: React.ChangeEvent<HTMLInputElement>) => {
                        const { value } = event.target;
                        updateUnifyForm({ title: value });
                    },
                    value: form.title,
                }}
            />
            <MultiUserInput
                className={classesInputBlock.root}
                onChange={(options: IComboBoxOption[]) => {
                    console.log(options);
                    updateUnifyForm({ authors: options });
                }}
                value={form.authors || []}
            />
            <DateRange
                onStartChange={(date: string) => {
                    updateUnifyForm({ startDate: date });
                }}
                onEndChange={(date: string) => {
                    updateUnifyForm({ endDate: date });
                }}
                start={form.startDate}
                end={form.endDate}
                className={classesDateRange.root}
            />
            <Button submit={true} className={classNames(classes.submit, buttons.standard)} prefix="submitButton">
                {results.status === LoadStatus.LOADING ? <ButtonLoader /> : t("Filter")}
            </Button>
        </form>
    );
}
