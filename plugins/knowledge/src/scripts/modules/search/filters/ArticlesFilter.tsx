import React from "react";
import { IUnifySearchFormState } from "@knowledge/modules/search/unifySearchPageReducer";
import Button from "@vanilla/library/src/scripts/forms/Button";
import { ButtonTypes } from "@vanilla/library/src/scripts/forms/buttonTypes";

interface IProps {
    fillQueryForm: (entry: Partial<IUnifySearchFormState>) => void;
    onSearch: () => void;
}

export default function ArticlesFilter(props: IProps) {
    const { fillQueryForm, onSearch } = props;

    return (
        <div>
            <h4> Search for articles</h4>
            <br /> <br />
            <div>
                <Button
                    baseClass={ButtonTypes.TEXT_PRIMARY}
                    onClick={() =>
                        fillQueryForm({
                            query: "article",
                            includeDeleted: true,
                            authors: [{ value: "2", label: "tuanng" }],
                        })
                    }
                >
                    Fill Articles Form
                </Button>
            </div>
            <br /> <br />
            <div>
                <Button baseClass={ButtonTypes.TEXT_PRIMARY} onClick={onSearch}>
                    Search Articles
                </Button>
            </div>
        </div>
    );
}
