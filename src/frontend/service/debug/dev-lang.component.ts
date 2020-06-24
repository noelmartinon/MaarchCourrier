import { Component, Inject, OnInit } from '@angular/core';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { LANG_EN } from '../../lang/lang-en';
import { LANG_FR } from '../../lang/lang-fr';
import { LANG_NL } from '../../lang/lang-nl';
import { HttpClient } from '@angular/common/http';
import { tap } from 'rxjs/internal/operators/tap';
import { catchError } from 'rxjs/operators';
import { of } from 'rxjs/internal/observable/of';

@Component({
    templateUrl: 'dev-lang.component.html',
    styleUrls: ['dev-lang.component.scss'],
})
export class DevLangComponent implements OnInit {
    allLang: any;
    enMissing = [];
    nlMissing = [];

    currentLang = 'en';

    constructor(@Inject(MAT_DIALOG_DATA) public data: any, public dialogRef: MatDialogRef<DevLangComponent>, public http: HttpClient) {
        this.allLang = {
            fr: JSON.parse(JSON.stringify(LANG_FR)),
            en: JSON.parse(JSON.stringify(LANG_EN)),
            nl: JSON.parse(JSON.stringify(LANG_NL))
        };
    }

    ngOnInit(): void {
        this.enMissing = Object.keys(this.allLang.fr).filter((keyLang: any) => Object.keys(this.allLang.en).indexOf(keyLang) === -1).map((keyLang: any) => {
            return {
                id: keyLang,
                value: this.allLang.fr[keyLang] + '__TO_TRANSLATE'
            };
        });
        this.nlMissing = Object.keys(this.allLang.fr).filter((keyLang: any) => Object.keys(this.allLang.nl).indexOf(keyLang) === -1).map((keyLang: any) => {
            return {
                id: keyLang,
                value:  this.allLang.fr[keyLang] + '__TO_TRANSLATE'
            };
        });
    }

    openTranslation(text: string) {
        window.open('https://translate.google.fr/?hl=fr#view=home&op=translate&sl=fr&tl=' + this.currentLang + '&text=' + text.replace('__TO_TRANSLATE', ''), '_blank');
    }

    setActiveLang(ev: any) {
        this.currentLang = ev.tab.textLabel;
    }

    generateMissingLang(ignoreToTranslate = false) {
        let newLang = {};
        let mergedLang = this.allLang[this.currentLang];
        const regex = /__TO_TRANSLATE$/g;

        if (this.currentLang === 'en') {
            this.enMissing.forEach(element => {
                if (element.value.match(regex) === null && ignoreToTranslate) {
                    newLang[element.id] = element.value;
                } else if (!ignoreToTranslate) {
                    newLang[element.id] = element.value;
                }
            });
            mergedLang = { ...mergedLang, ...newLang };
        } else {
            this.nlMissing.forEach(element => {
                if (element.value.match(regex) === null && ignoreToTranslate) {
                    newLang[element.id] = element.value;
                } else if (!ignoreToTranslate) {
                    newLang[element.id] = element.value;
                }
            });
            mergedLang = { ...mergedLang, ...newLang };
        }
        this.http.put('../rest/dev/lang', { langId: this.currentLang, jsonContent: mergedLang }).pipe(
            tap((data: any) => {
                Object.keys(newLang).forEach(keyLang => {
                    console.log(keyLang);
                    console.log(this.enMissing);

                    delete this.allLang[this.currentLang][keyLang];
                    if (this.currentLang === 'en') {
                        this.enMissing = this.enMissing.filter((missLang) => missLang.id !== keyLang);
                    } else {
                        this.nlMissing = this.enMissing.filter((missLang) => missLang.id !== keyLang);
                    }
                });
            }),
            catchError((err: any) => {
                console.log(err);
                return of(false);
            })
        ).subscribe();
    }
}
