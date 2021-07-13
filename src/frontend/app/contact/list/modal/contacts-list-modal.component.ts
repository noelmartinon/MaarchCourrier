import { Component, Inject } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { LANG } from '../../../translate.component';
import { HttpClient } from '@angular/common/http';

@Component({
    templateUrl: 'contacts-list-modal.component.html',
    styleUrls: ['contacts-list-modal.component.scss'],
})
export class ContactsListModalComponent {
    lang: any = LANG;

    constructor(
        public http: HttpClient,
        @Inject(MAT_DIALOG_DATA) public data: any,
        public dialogRef: MatDialogRef<ContactsListModalComponent>) {
    }

    ngOnInit(): void { }
}
