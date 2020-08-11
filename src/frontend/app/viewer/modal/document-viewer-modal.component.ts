import { Component, OnInit, Inject } from '@angular/core';
import { LANG } from '../../translate.component';
import { TranslateService } from '@ngx-translate/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';

@Component({
    templateUrl: "document-viewer-modal.component.html",
    styleUrls: ['document-viewer-modal.component.scss'],
})
export class DocumentViewerModalComponent implements OnInit {

    lang: any = LANG;
    loading: boolean = false;

    constructor(private translate: TranslateService, public dialogRef: MatDialogRef<DocumentViewerModalComponent>, @Inject(MAT_DIALOG_DATA) public data: any) { }

    ngOnInit(): void { }
}
