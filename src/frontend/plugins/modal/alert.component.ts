import { Component, Inject } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { LANG } from '../../app/translate.component';

@Component({
    templateUrl: 'alert.component.html',
    styleUrls: ['alert.component.scss']
})
export class AlertComponent {

    lang: any = LANG;

    constructor(@Inject(MAT_DIALOG_DATA) public data: any, public dialogRef: MatDialogRef<AlertComponent>) {
        if (this.data.mode === null || this.data.mode === undefined) {
            this.data.mode = 'info';
        }
        this.data.mode = 'alert-message-' + this.data.mode;
        if (this.data.msg === null) {
            this.data.msg = '';
        }
    }
}
