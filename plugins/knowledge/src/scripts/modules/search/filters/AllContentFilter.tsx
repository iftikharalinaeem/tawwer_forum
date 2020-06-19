import React from "react";
import { IUnifySearchFormState } from "@knowledge/modules/search/unifySearchPageReducer";
import Button from "@vanilla/library/src/scripts/forms/Button";
import { ButtonTypes } from "@vanilla/library/src/scripts/forms/buttonTypes";

interface IProps {
    fillQueryForm: (entry: Partial<IUnifySearchFormState>) => void;
    onSearch: () => void;
}

export default function AllContentFilter(props: IProps) {
    const { fillQueryForm, onSearch } = props;

    return (
        <div>
            <h4> Search for all content</h4>
            <br /> <br />
            <div>
                <Button baseClass={ButtonTypes.TEXT_PRIMARY} onClick={() => fillQueryForm({ query: "article" })}>
                    Fill All Content Form
                </Button>
            </div>
            <br /> <br />
            <div>
                <Button baseClass={ButtonTypes.TEXT_PRIMARY} onClick={onSearch}>
                    Search All Content
                </Button>
            </div>
        </div>
    );
}
