import { Component, Inject } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { LANG } from '../../translate.component';
import { TranslateService } from '@ngx-translate/core';
import { HttpClient } from '@angular/common/http';
import { tap, catchError } from 'rxjs/operators';
import { NotificationService } from '../../../service/notification/notification.service';
import { of } from 'rxjs';

@Component({
    templateUrl: 'add-avis-model-modal.component.html',
    styleUrls: ['add-avis-model-modal.component.scss'],
})
export class AddAvisModelModalComponent {
    lang: any = LANG;

    loading:boolean = false;

    template: any = {
        id: 0,
        type: 'opinionCircuit',
        title: '',
        items : []
    };

    constructor(
        private translate: TranslateService,
        public http: HttpClient, 
        @Inject(MAT_DIALOG_DATA) public data: any, 
        public dialogRef: MatDialogRef<AddAvisModelModalComponent>,
        private notify: NotificationService) { }

    ngOnInit(): void {
        this.template.items = this.data.avisWorkflow.map((item: any) => {
            return {
                id: item.item_id,
                type: 'user',
                mode: 'avis'
            }
        }); 
    }

    onSubmit() {
        this.http.post(`../rest/listTemplates`, this.template).pipe(
            tap((data: any) => {
                this.template.id = data.id;
                this.notify.success(this.translate.instant('lang.modelSaved'));
                this.dialogRef.close(this.template);
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

}
