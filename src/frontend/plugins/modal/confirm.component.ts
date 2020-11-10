import { Component, Inject } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { LANG } from '../../app/translate.component';
import { HeaderService } from '../../service/header.service';
import { LocalStorageService } from '../../service/local-storage.service';

@Component({
    templateUrl: 'confirm.component.html',
    styleUrls: ['confirm.component.scss']
})
export class ConfirmComponent {

    lang: any = LANG;
    idModal: string = null;

    constructor(
        @Inject(MAT_DIALOG_DATA) public data: any,
        public dialogRef: MatDialogRef<ConfirmComponent>,
        public headerService: HeaderService,
        private localStorage: LocalStorageService
    ) {
        if (this.data.idModal !== undefined) {
            this.idModal = this.data.idModal;
        }

        if (this.data.msg === null) {
            this.data.msg = '';
        }

        if (this.data.buttonCancel === undefined) {
            this.data.buttonCancel = this.lang.cancel;
        }

        if (this.data.buttonValidate === undefined) {
            this.data.buttonValidate = this.lang.ok;
        }
    }

    hideModal() {
        if (this.idModal !== '') {
            this.localStorage.save(`modal_${this.idModal}_${this.headerService.user.id}`, true);
        } else {
            alert('No idModal provided!');
        }
    }
}
