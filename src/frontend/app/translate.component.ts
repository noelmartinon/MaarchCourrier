import { LANG_EN } from '../lang/lang-en';
import { LANG_FR } from '../lang/lang-fr';
import { LANG_NL } from '../lang/lang-nl';

declare var angularGlobals : any;

var dictionary = {};
if (angularGlobals.lang == "en") {
    dictionary = LANG_EN;
} else if (angularGlobals.lang == "fr") {
    dictionary = LANG_FR;
} else if (angularGlobals.lang == "nl") {
    dictionary = LANG_NL;
}

if (angularGlobals.customLanguage != null) {
    dictionary = {...dictionary, ...angularGlobals.customLanguage};
}

export const LANG = dictionary;