import { ContentTranslationProvider } from "@vanilla/i18n";
import { ContentTranslator } from "../translationGrid/ContentTranslator";

console.log("hello world");
ContentTranslationProvider.setTranslator(ContentTranslator);
