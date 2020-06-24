import { Component, OnInit } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { DevLangComponent } from './dev-lang.component';
import { LANG_EN } from '../../lang/lang-en';
import { LANG_FR } from '../../lang/lang-fr';
import { LANG_NL } from '../../lang/lang-nl';

@Component({
    selector: 'app-dev-tool',
    templateUrl: 'dev-tool.component.html',
    styleUrls: ['dev-tool.component.scss'],
})
export class DevToolComponent implements OnInit {

    allLang: any;
    countMissingLang = 0;

    constructor(public dialog: MatDialog) { }

    ngOnInit(): void {
        this.allLang = {
            fr: JSON.parse(JSON.stringify(LANG_FR)),
            en: JSON.parse(JSON.stringify(LANG_EN)),
            nl: JSON.parse(JSON.stringify(LANG_NL))
        };

        this.countMissingLang = Object.keys(this.allLang.fr).filter((keyLang: any) => Object.keys(this.allLang.en).indexOf(keyLang) === -1).length + Object.keys(this.allLang.fr).filter((keyLang: any) => Object.keys(this.allLang.nl).indexOf(keyLang) === -1).length;

    }

    openLangTool() {
        this.dialog.open(DevLangComponent, {
            panelClass: 'maarch-modal',
            width: '80%',
            data: ''
        });
    }

}
