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

    shippingAttachments: any [] = [
        {
            'attachments': [
                {
                    attachmentType: 'summarySheet',
                    date: '2021-11-10T16:00:00Z',
                    id: 0,
                    label: null,
                    resourceId: 'foo-bar',
                    resourceType: 'registered_mail/v2/sendings'
                },
                {
                    attachmentType: 'acknowledgementReceipt',
                    date: '2021-11-10T16:00:00Z',
                    id: 1,
                    label: 'Bernard PASCONTENT (Monstres & Cie.)',
                    resourceId: 'batman',
                    resourceType: 'registered_mail/v2/recipients'
                }
            ]
        }

    ];

    shippingHistory: any[] = [
        {
            eventDate: '2021-09-10T16:00:00Z',
            eventType: this.translate.instant('lang.ON_ACKNOWLEDGEMENT_OF_RECEIPT_RECEIVED'),
            resourceId: 'batman',
            resourceType: 'registered_mail/v2/recipients',
            status: 'END'
        },
        {
            eventDate: '2021-10-10T16:00:00Z',
            eventType: this.translate.instant('lang.ON_DEPOSIT_PROOF_RECEIVED'),
            resourceId: 'foo-bar',
            resourceType: 'registered_mail/v2/sendings',
            status: 'ATT'
        },
        {
            eventDate: '2021-11-10T16:00:00Z',
            eventType: this.translate.instant('lang.ON_ACKNOWLEDGEMENT_OF_RECEIPT_RECEIVED'),
            resourceId: 'batman',
            resourceType: 'registered_mail/v2/recipients',
            status: 'END'
        }
    ];

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
        this.data.row.creationDate = this.fullDate.transform(new Date(this.data.row.creationDate).toString());
        this.data.row.sendDate = this.fullDate.transform(new Date(this.data.row.sendDate).toString());
        this.loading = false;
        // await this.getShippingHistory(this.data.shippingId);
    }

    getShippingHistory(shippingId: number) {
        return new Promise((resolve) => {
            this.http.get(`../rest/shippings/${shippingId}/history`).pipe(
                tap((data: any) => {
                    console.log('data', data);
                    this.shippingHistory = data;
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
}
