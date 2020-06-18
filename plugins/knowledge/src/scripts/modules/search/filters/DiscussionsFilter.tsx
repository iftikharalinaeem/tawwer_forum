import React from "react";
import { IUnifySearchFormState } from "@knowledge/modules/search/unifySearchPageReducer";
import Button from "@vanilla/library/src/scripts/forms/Button";
import { ButtonTypes } from "@vanilla/library/src/scripts/forms/buttonTypes";

interface IProps {
    fillLiveForm: (entry: Partial<IUnifySearchFormState>) => void;
    onSearch: () => void;
}

export default function DiscussionsFilter(props: IProps) {
    const { fillLiveForm, onSearch } = props;

    return (
        <div>
            <h4> Search for discussions</h4>
            <br /> <br />
            <div>
                <Button
                    baseClass={ButtonTypes.TEXT_PRIMARY}
                    onClick={() =>
                        fillLiveForm({ query: "discussion", startDate: "2020-06-10", endDate: "2020-06-17" })
                    }
                >
                    Fill Discussion Form
                </Button>
            </div>
            <br /> <br />
            <div>
                <Button baseClass={ButtonTypes.TEXT_PRIMARY} onClick={onSearch}>
                    Search Discussions
                </Button>
            </div>
        </div>
    );
}
