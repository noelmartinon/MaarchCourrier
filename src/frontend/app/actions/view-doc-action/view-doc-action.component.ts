import { Component, OnInit, Inject } from '@angular/core';
import { LANG } from '../../translate.component';
import { TranslateService } from '@ngx-translate/core';
import { MAT_DIALOG_DATA } from '@angular/material/dialog';

@Component({
    templateUrl: "view-doc-action.component.html",
    styleUrls: ['view-doc-action.component.scss'],
})
export class ViewDocActionComponent implements OnInit {

    lang: any = LANG;

    constructor(
        private translate: TranslateService,
        @Inject(MAT_DIALOG_DATA) public data: any
    ) { }

    ngOnInit(): void { }
}
