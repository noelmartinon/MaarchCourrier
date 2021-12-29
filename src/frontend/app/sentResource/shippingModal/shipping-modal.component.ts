import { Component, OnInit, Inject } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import { MatDialog, MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { catchError, map, tap } from 'rxjs/operators';
import { FunctionsService } from '@service/functions.service';
import { ContactService } from '@service/contact.service';
import { AppService } from '@service/app.service';
import { PrivilegeService } from '@service/privileges.service';
import { HeaderService } from '@service/header.service';
import { Observable, of } from 'rxjs';
import { FullDatePipe } from '@plugins/fullDate.pipe';
import { AuthService } from '@service/auth.service';

@Component({
    templateUrl: 'shipping-modal.component.html',
    styleUrls: ['shipping-modal.component.scss'],
    providers: [ContactService, AppService, FullDatePipe],
})

export class ShippingModalComponent implements OnInit {

    loading: boolean = true;

    shippingAttachments: any [] = [];
    shippingHistory: any[] = [];
    status: any[] = [];

    depositProof: any = null;
    creationDate: any;
    sendDate: any;

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        public dialog: MatDialog,
        public dialogRef: MatDialogRef<ShippingModalComponent>,
        public functions: FunctionsService,
        public privilegeService: PrivilegeService,
        public headerService: HeaderService,
        public translate: TranslateService,
        @Inject(MAT_DIALOG_DATA) public data: any,
        private fullDate: FullDatePipe,
        private authService: AuthService
    ) {}

    async ngOnInit() {
        await this.getStatus();
        await this.getAttachments();
        await this.getShippingHistory();
        this.creationDate = this.fullDate.transform(new Date(this.data.shippingData.creationDate).toString());
        this.sendDate = this.fullDate.transform(new Date(this.data.shippingData.sendDate).toString());
        this.loading = false;
    }

    getAttachments() {
        return new Promise((resolve) => {
            this.http.get(`../rest/shippings/${this.data.shippingData.id}/attachments`).pipe(
                tap((data: any) => {
                    if (data.attachments.length > 0) {
                        this.depositProof = data.attachments.find((item: any) => item.attachmentType === 'shipping_deposit_proof');
                        this.shippingAttachments = data.attachments.filter((item: any) => item.attachmentType === 'shipping_acknowledgement_of_receipt');
                    }
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    getShippingHistory() {
        return new Promise((resolve) => {
            this.http.get(`../rest/shippings/${this.data.shippingData.id}/history`).pipe(
                tap((data: any) => {
                    if (data.history.length > 0) {
                        this.shippingHistory = data.history.filter((history: any) => ['ON_DEPOSIT_PROOF_RECEIVED', 'ON_ACKNOWLEDGEMENT_OF_RECEIPT_RECEIVED'].indexOf(history.eventType) === -1);
                    }
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    downloadFile(resId: number) {
        const downloadLink = document.createElement('a');
        this.http.get(`../rest/attachments/${resId}/originalContent?mode=base64`).pipe(
            tap((data: any) => {
                downloadLink.href = `data:${data.mimeType};base64,${data.encodedDocument}`;
                downloadLink.setAttribute('download', data.filename);
                document.body.appendChild(downloadLink);
                downloadLink.click();
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();

    }

    getStatus() {
        return new Promise((resolve) => {
            this.http.get('../rest/statuses').pipe(
                map((data: any) => data.statuses),
                tap((data: any) => {
                    this.status = data;
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    setStatus(status: string) {
        return this.status.find((element: any) => element.id === status).label_status;
    }
}
