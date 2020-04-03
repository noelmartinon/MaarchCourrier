import { Component, OnInit, Input, EventEmitter, Output } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { catchError, tap, finalize, filter } from 'rxjs/operators';
import { of } from 'rxjs';
import { NotificationService } from '../../notification.service';
import { MatDialog, MatDialogRef } from '@angular/material/dialog';
import { AttachmentPageComponent } from '../attachments-page/attachment-page.component';


@Component({
    selector: 'app-attachments-resume',
    templateUrl: "attachments-resume.component.html",
    styleUrls: [
        'attachments-resume.component.scss',
    ]
})

export class AttachmentsResumeComponent implements OnInit {

    lang: any = LANG;

    loading: boolean = true;

    attachments: any[] = [];

    @Input('resId') resId: number = null;
    @Output('goTo') goTo = new EventEmitter<string>();

    dialogRef: MatDialogRef<any>;
    
    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        public dialog: MatDialog,
    ) {
    }

    ngOnInit(): void {
        this.loading = true;
        this.loadAttachments(this.resId);
    }

    loadAttachments(resId: number) {
        this.loading = true;
        this.http.get(`../../rest/resources/${resId}/attachments?limit=3`).pipe(
            tap((data: any) => {
                this.attachments = data.attachments;
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    showAttachment(attachment: any) {
        this.dialogRef = this.dialog.open(AttachmentPageComponent, { height: '99vh', width: '99vw', disableClose: true, panelClass: 'modal-container', data: { resId: attachment.resId} });

        this.dialogRef.afterClosed().pipe(
            filter((data: string) => data === 'success'),
            tap(() => {
                this.loadAttachments(this.resId);
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    showMore() {
        this.goTo.emit();
    }
}