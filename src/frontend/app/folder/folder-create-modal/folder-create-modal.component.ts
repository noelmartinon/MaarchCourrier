import { Component, OnInit } from '@angular/core';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../notification.service';
import { MatDialogRef } from '@angular/material/dialog';
import { HttpClient } from '@angular/common/http';
import { tap, finalize, catchError } from 'rxjs/operators';
import { of } from 'rxjs';

@Component({
    templateUrl: "folder-create-modal.component.html",
    styleUrls: ['folder-create-modal.component.scss'],
})
export class FolderCreateModalComponent implements OnInit {

    lang: any = LANG;
    loading: boolean = false;
    folderName: string = '';

    constructor(
        public http: HttpClient, 
        private notify: NotificationService, 
        public dialogRef: MatDialogRef<FolderCreateModalComponent>
    ) { }

    ngOnInit(): void { 

    }

    onSubmit() {
        this.loading = true;
        this.http.post("../../rest/folders", { label: this.folderName }).pipe(
            tap(() => {
                this.notify.success(this.lang.folderAdded);
                this.dialogRef.close('success');
            }),
            finalize(() => this.loading = false),
            catchError((err) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

}
