/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ArticleActions from "@knowledge/modules/article/ArticleActions";
import { reactionClasses } from "@knowledge/modules/article/components/articleReactionStyles";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import Translate from "@library/content/Translate";
import { isUserGuest, IUsersStoreState } from "@library/features/users/userModel";
import Button from "@library/forms/Button";
import Heading from "@library/layout/Heading";
import Paragraph from "@library/layout/Paragraph";
import ButtonLoader from "@library/loaders/ButtonLoader";
import SmartLink from "@library/routing/links/SmartLink";
import { ensureReCaptcha, t } from "@library/utility/appUtils";
import classNames from "classnames";
import React from "react";
import { connect } from "react-redux";
import { ArticleReactionType, IArticleReaction } from "@knowledge/@types/api/article";
import { CheckCompactIcon } from "@library/icons/common";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { ArticleReactionsEvent } from "@knowledge/modules/article/components/ArticleReactionsEvent";
import { userContentClasses } from "@library/content/userContentStyles";

export function ArticleReactions(props: IProps) {
    const { isNoSubmitting, isYesSubmitting, isSignedIn } = props;

    const classes = reactionClasses();
    const helpfulReactions = props.reactions.find(article => article.reactionType === ArticleReactionType.HELPFUL);

    // If we don't have reaction data bail out early.
    if (!helpfulReactions) {
        return null;
    }

    // Build the text for the view.
    let { yes, total } = helpfulReactions;

    let localeStorage = typeof localStorage !== "undefined" ? localStorage : false;
    let disableGuestVoting = !localeStorage;
    let reCaptchaText = <></>;

    if (!isSignedIn && localeStorage) {
        ensureReCaptcha();

        reCaptchaText = (
            <div className={classNames(userContentClasses().root, classes.reCaptchaContainer)}>
                <Paragraph className={classNames(classes.reCaptchaText)}>
                    <Translate source="This site is protected by reCAPTCHA and the Google " />
                    <Translate
                        source="<0>Privacy Policy</0> and <1>Terms of Service</1>"
                        c0={content => <a href="https://policies.google.com/privacy">{content}</a>}
                        c1={content => <a href="https://policies.google.com/terms">{content}</a>}
                    />
                </Paragraph>
            </div>
        );

        if (localeStorage) {
            try {
                const hasVoted = localeStorage.getItem(`hasVoted-${props.articleID}`);
                if (!hasVoted && (isYesSubmitting || isNoSubmitting)) {
                    localeStorage.setItem(`hasVoted-${props.articleID}`, isYesSubmitting ? "yes" : "no");
                } else if (hasVoted) {
                    const userReactionType = hasVoted === "yes" ? "yes" : "no";
                    helpfulReactions.userReaction = userReactionType;
                    if (userReactionType === "yes") {
                        yes = total === 1 ? 1 : yes + 1;
                    }
                    total = total === 1 ? 1 : total + 1;
                }
            } catch (e) {
                disableGuestVoting = true;
            }
        }
    }

    const { userReaction } = helpfulReactions;

    const resultText =
        total < 1 ? (
            t("Be the first one to vote!")
        ) : (
            <>
                <Translate source="<0 /> out of <1 /> people found this helpful" c0={yes} c1={total} />
            </>
        );

    const title = userReaction !== null ? t("Thanks for your feedback!") : t("Was this article helpful?");

    const buttonsDisabled = isYesSubmitting || isNoSubmitting || userReaction !== null || disableGuestVoting;

    return (
        <>
            <section className={classes.frame}>
                <Heading title={title} className={classes.title} />
                <div className={classes.votingButtons}>
                    <ReactionButton
                        reactionData={helpfulReactions}
                        title={t("Yes")}
                        reactionValue="yes"
                        isSubmitting={!!isYesSubmitting}
                        isDisabled={buttonsDisabled}
                        onClick={props.onYesClick}
                    />
                    <ReactionButton
                        reactionData={helpfulReactions}
                        title={t("No")}
                        reactionValue="no"
                        isSubmitting={!!isNoSubmitting}
                        isDisabled={buttonsDisabled}
                        onClick={props.onNoClick}
                    />
                </div>
                {disableGuestVoting && <SignInLink isSignedIn={props.isSignedIn} />}
                <Paragraph className={classes.resultText}>{resultText}</Paragraph>
                {reCaptchaText}
            </section>
            <ArticleReactionsEvent />
        </>
    );
}

/**
 * Small subcomponent for rendering a single reaction button.
 */
function ReactionButton(props: {
    reactionData: IArticleReaction;
    title: string;
    reactionValue: string;
    isSubmitting: boolean;
    isDisabled: boolean;
    onClick: () => void;
}) {
    const { reactionValue, reactionData, title, isSubmitting, isDisabled, onClick } = props;
    const { userReaction } = reactionData;
    const styles = reactionClasses();

    // Content can be either a checkbox, a loader, or some text.
    let content: React.ReactNode = title;
    const checked = userReaction === reactionValue;
    if (checked) {
        content = (
            <span className={styles.checkedButtonContent}>
                <CheckCompactIcon />
            </span>
        );
    }
    if (isSubmitting) {
        content = <ButtonLoader />;
    }
    const classes = classNames(
        {
            [styles.checkedButton]: userReaction === reactionValue,
        },
        styles.votingButton,
    );

    return (
        <Button
            baseClass={checked ? ButtonTypes.PRIMARY : ButtonTypes.STANDARD}
            disabled={isDisabled}
            className={classes}
            onClick={onClick}
        >
            {content}
        </Button>
    );
}

/**
 * Small subcomponent for rendering a sign in prompt for signed out users.
 */
function SignInLink(props: { isSignedIn: boolean }) {
    if (props.isSignedIn) {
        return null;
    }

    const classes = reactionClasses();

    // Signin Link
    const signInLinkCallback = content => (
        <SmartLink to={`/entry/signin?target=${window.location.pathname}`} className={classes.link}>
            {content}
        </SmartLink>
    );

    return (
        <Paragraph className={classes.signInText}>
            <Translate source={"You need to <0>Sign In</0> to vote on this article"} c0={signInLinkCallback} />
        </Paragraph>
    );
}

interface IOwnProps {
    articleID: number;
    reactions: IArticleReaction[];
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

/**
 * Map in state from the redux store.
 */
function mapStateToProps(state: IUsersStoreState & IKnowledgeAppStoreState, ownProps: IOwnProps) {
    const currentUser = state.users.current;
    const reactionLoadable = state.knowledge.articlePage.reactionLoadable;
    let isSignedIn = false;
    if (currentUser.data && !isUserGuest(currentUser.data)) {
        isSignedIn = true;
    }
    return {
        isSignedIn,
        isYesSubmitting:
            reactionLoadable.status === LoadStatus.LOADING &&
            reactionLoadable.data &&
            reactionLoadable.data.reaction === "yes",
        isNoSubmitting:
            reactionLoadable.status === LoadStatus.LOADING &&
            reactionLoadable.data &&
            reactionLoadable.data.reaction === "no",
    };
}

/**
 * Map in some bound actions.
 */
function mapDispatchToProps(dispatch, ownProps: IOwnProps) {
    const { articleID } = ownProps;
    const articleActions = new ArticleActions(dispatch, apiv2);

    return {
        onYesClick: () =>
            articleActions.reactHelpful({
                articleID,
                helpful: "yes",
            }),
        onNoClick: () =>
            articleActions.reactHelpful({
                articleID,
                helpful: "no",
            }),
    };
}

export default connect(mapStateToProps, mapDispatchToProps)(ArticleReactions);