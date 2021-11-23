import { Component, OnInit, Inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import { MatDialog, MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { catchError, map, tap } from 'rxjs/operators';
import { FunctionsService } from '@service/functions.service';
import { ContactService } from '@service/contact.service';
import { AppService } from '@service/app.service';
import { PrivilegeService } from '@service/privileges.service';
import { HeaderService } from '@service/header.service';
import { of } from 'rxjs';
import { FullDatePipe } from '@plugins/fullDate.pipe';
import { resolve } from 'dns';

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
        private fullDate: FullDatePipe
    ) {}

    async ngOnInit() {
        await this.getStatus();
        await this.getAttachments();
        await this.getShippingHistory();
        this.data.shippingData.creationDate = this.fullDate.transform(new Date(this.data.shippingData.creationDate).toString());
        this.data.shippingData.sendDate = this.fullDate.transform(new Date(this.data.shippingData.sendDate).toString());
        this.loading = false;
    }

    getAttachments() {
        return new Promise((resolve) => {
            this.http.get(`../rest/shippings/${this.data.shippingData.id}/attachments`).pipe(
                tap((data: any) => {
                    console.log('attachments', data.attachments);
                    this.shippingAttachments = data.attachments;
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
                    console.log('history', data.history);
                    this.shippingHistory = data.history;
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    downloadFile(fileId: number) {
        this.http.get(`../rest/shippings/${this.data.shippingData.id}/attachments/${fileId}`).pipe(
            tap((data: any) => {
                const downloadLink = document.createElement('a');
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
