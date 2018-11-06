/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */
import * as React from "react";
import classNames from "classnames";
import InputBlock, { InputTextBlockBaseClass } from "@library/components/forms/InputBlock";
import { t } from "@library/application";
import DateInput from "@library/components/forms/DateInput";

interface IProps {
    // start: string;
    // end: string;
    // onStartChange: (value: string) => void;
    // onEndChange: (value: string) => void;
    className?: string;
}

interface IState {}

/**
 * Implements the DateRange component
 */
export default class DateRange extends React.PureComponent<IProps> {
    public render() {
        return (
            <div className={classNames("dateRange inputBlock", this.props.className)}>
                <div className="dateRange-row">
                    <InputBlock
                        label={t("Date from")}
                        baseClass={InputTextBlockBaseClass.CUSTOM}
                        className="dateRange-column"
                    >
                        <DateInput />
                    </InputBlock>
                    <InputBlock label={t("To")} baseClass={InputTextBlockBaseClass.CUSTOM} className="dateRange-column">
                        <DateInput alignment="right" />
                    </InputBlock>
                </div>
            </div>
        );
    }
}
