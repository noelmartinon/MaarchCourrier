import { Component, Inject } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { LANG } from '../../../translate.component';
import { HttpClient } from '@angular/common/http';

@Component({
    templateUrl: 'contact-resource-modal.component.html',
    styleUrls: ['contact-resource-modal.component.scss'],
})
export class ContactResourceModalComponent {
    lang: any = LANG;

    constructor(
        public http: HttpClient,
        @Inject(MAT_DIALOG_DATA) public data: any,
        public dialogRef: MatDialogRef<ContactResourceModalComponent>) {
    }
}
