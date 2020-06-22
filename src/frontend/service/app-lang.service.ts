import { Injectable } from '@angular/core';
import { LANG_EN } from '../../frontend/lang/lang-en';
import { LANG_FR } from '../../frontend/lang/lang-fr';
import { LANG_NL } from '../../frontend/lang/lang-nl';

@Injectable({
    providedIn: 'root'
})
export class LangService {

    lang: string = 'FR';

    constructor() {
        console.log('init lang!');
    }

    setLang(lang: string) {
        this.lang = lang;
    }

    getLang() {
        return LANG_FR;
    }
}
