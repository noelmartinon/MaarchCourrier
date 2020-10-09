import { HttpClient } from '@angular/common/http';
import { Component, Inject, OnInit } from '@angular/core';
import { FormBuilder, FormControl, FormGroup, Validators } from '@angular/forms';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { TranslateService } from '@ngx-translate/core';
import { FunctionsService } from '@service/functions.service';
import { NotificationService } from '@service/notification/notification.service';
import { of } from 'rxjs';
import { catchError, finalize, tap } from 'rxjs/operators';

@Component({
    selector: 'app-send-to-record-management',
    templateUrl: 'send-to-record-management.component.html',
    styleUrls: ['send-to-record-management.component.scss']
})
export class SendToRecordManagementComponent implements OnInit {

    loading: boolean = false;
    archiveSenders: [
        {
            id: 1,
            label: 'test'
        }
    ];
    archivalAgreement: [
        {
            id: 1,
            label: 'test'
        }
    ];

    actionFormGroup: FormGroup;

    archives: any[] = [
        {
            id : 'letterbox_100',
            label : 'Procédure INSTALLATION du patch MAARCH 1_7_ARCHIVAGE',
            type : 'Document principal',
            descService : '???'
        },
        {
            id : 'attachment_1_1',
            label : 'Réponse de Jean',
            type : 'Projet de réponse',
            descService : '???'
        }
    ];


    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        private notify: NotificationService,
        public dialogRef: MatDialogRef<SendToRecordManagementComponent>,
        private _formBuilder: FormBuilder,
        @Inject(MAT_DIALOG_DATA) public data: any,
        public functions: FunctionsService
    ) {
        this.actionFormGroup = this._formBuilder.group({
            packageName: ['test', Validators.required],
            slipId: [{value: 'bblier-20201008-123809', disabled: true}, Validators.required],
            slipDate: [new Date(), Validators.required],
            entityLabelTransferEntity: [{value: 'Direction Général des services', disabled: true}, Validators.required],
            entitySirenTransferEntity: [{value: 'org_987654321_DGS_SA', disabled: true}, Validators.required],
            sirenArchiveEntity: [{value: 'org_123456789_Archives', disabled: true}, Validators.required],
            archiveId: [{value: 'letterbox_100', disabled: true}, Validators.required],
            doctype: [{value: 'Convocation', disabled: true}, Validators.required],
            entityRetentionRule: [{value: 'compta_3_03', disabled: true}, Validators.required],
            doctypeRetentionFinalDisposition: [{value: 'Destruction', disabled: true}, Validators.required],
            doctypeDurationCurrentUse: [{value: '23 jours', disabled: true}, Validators.required],
            doctypeActionCurrentUse: [{value: 'Envoi SAE (puis accès restreint)', disabled: true}, Validators.required],
            sendDate: ['??? (voir equipe RM)', Validators.required],
            receiveDate: ['??? (voir equipe RM)', Validators.required],
        });
    }

    ngOnInit(): void {
        this.getData();
    }

    getData() {

        // TODO
        /*this.http.post(`../rest/resourcesList/users/${this.data.userId}/groups/${this.data.groupId}/baskets/${this.data.basketId}/actions/${this.data.action.id}/checkSendToRecordManagement`, { resources: this.data.resIds }).pipe(
            tap((data: any) => {
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();*/
    }

    onSubmit() {
        this.loading = true;

        if (this.data.resIds.length > 0) {
            this.executeAction();
        }
    }

    executeAction() {

        const realResSelected: number[] = this.data.resIds;

        this.http.put(this.data.processActionRoute, { resources: realResSelected, data: {} }).pipe(
            tap((data: any) => {
                if (!data) {
                    this.dialogRef.close('success');
                }
                if (data && data.errors != null) {
                    this.notify.error(data.errors);
                }
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    isValidAction() {
        return true;
    }

}
