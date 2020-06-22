import { Component, Inject, ViewChild } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { LANG } from '../../translate.component';
import { HttpClient } from '@angular/common/http';
import { tap, catchError } from 'rxjs/operators';
import { NotificationService } from '../../../service/notification/notification.service';
import { of } from 'rxjs';

@Component({
    templateUrl: 'add-visa-model-modal.component.html',
    styleUrls: ['add-visa-model-modal.component.scss'],
})
export class AddVisaModelModalComponent {
    lang: any = LANG;

    loading: boolean = false;

    template: any = {
        id: 0,
        type: 'visaCircuit',
        title: '',
        items : []
    };

    constructor(
        public http: HttpClient, 
        @Inject(MAT_DIALOG_DATA) public data: any, 
        public dialogRef: MatDialogRef<AddVisaModelModalComponent>,
        private notify: NotificationService) { }

    ngOnInit(): void {
        this.template.items = this.data.visaWorkflow.map((item: any) => {
            return {
                id: item.item_id,
                type: 'user',
                mode: item.requested_signature ? 'sign' : 'visa'
            }
        }); 
    }

    onSubmit() {
        this.http.post(`../rest/listTemplates`, this.template).pipe(
            tap((data: any) => {
                this.template.id = data.id;
                this.notify.success(this.lang.modelSaved);
                this.dialogRef.close(this.template);
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

}
