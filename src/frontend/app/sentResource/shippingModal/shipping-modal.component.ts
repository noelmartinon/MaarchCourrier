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
                    this.depositProof = data.attachments.find((item: any) => item.attachmentType === 'depositProof');
                    this.shippingAttachments = data.attachments.filter((item: any) => item.attachmentType !== 'depositProof');
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
        const headers = new HttpHeaders({
            'Authorization': 'Bearer ' + this.authService.getToken()
        });
        return new Observable<string>((observer) => {
            const { next, error } = observer;
            this.http.get(`../rest/shippings/${this.data.shippingData.id}/attachments/${fileId}`, { headers: headers, responseType: 'blob' }).subscribe(response => {
                const reader = new FileReader();
                reader.readAsDataURL(response);
                reader.onloadend = () => {
                    observer.next(reader.result as any);
                    const href: string = reader.result as string;
                    const downloadLink = document.createElement('a');
                    const fileName: string = this.shippingAttachments.find((el: any) => el.id === fileId) === undefined ? this.translate.instant('lang.depositProof') : this.shippingAttachments.find((el: any) => el.id === fileId).label;
                    downloadLink.href = href;
                    downloadLink.setAttribute('download', fileName);
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                };
            });
        }).subscribe();

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
