import { ContentTranslationProvider } from "@vanilla/i18n";
import { ContentTranslator } from "../translator/ContentTranslator";
import { registerReducer } from "@library/redux/reducerRegistry";
import { translationReducer } from "../translator/translationReducer";

ContentTranslationProvider.setTranslator(ContentTranslator);
registerReducer("translations", translationReducer);
