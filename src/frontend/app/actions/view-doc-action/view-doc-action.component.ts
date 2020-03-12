import { Component, OnInit, Inject } from '@angular/core';
import { LANG } from '../../translate.component';
import { MAT_DIALOG_DATA } from '@angular/material/dialog';

@Component({
    templateUrl: "view-doc-action.component.html",
    styleUrls: ['view-doc-action.component.scss'],
})
export class ViewDocActionComponent implements OnInit {

    lang: any = LANG;

    constructor(
        @Inject(MAT_DIALOG_DATA) public data: any
    ) { }

    ngOnInit(): void { }
}
