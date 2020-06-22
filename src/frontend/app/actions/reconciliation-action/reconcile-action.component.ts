import { Component, OnInit, Inject, ViewChild } from '@angular/core';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../../service/notification/notification.service';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { HttpClient } from '@angular/common/http';
import { NoteEditorComponent } from '../../notes/note-editor.component';
import { tap, exhaustMap, finalize, catchError } from 'rxjs/operators';
import { of } from 'rxjs';
import { FunctionsService } from '../../../service/functions.service';
import { SearchAdvListComponent } from '../../adv-search/list/search-adv-list.component';

@Component({
    templateUrl: "reconcile-action.component.html",
    styleUrls: ['reconcile-action.component.scss'],
})
export class ReconcileActionComponent implements OnInit {

    lang: any = LANG;
    loading: boolean = false;

    @ViewChild('noteEditor', { static: false }) noteEditor: NoteEditorComponent;
    @ViewChild('appSearchAdvList', { static: false }) appSearchAdvList: SearchAdvListComponent;

    searchUrl: string = '';
    resourcesErrors: any[] = [];
    selectedRes: number[] = [];
    noResourceToProcess: boolean = false;

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        public dialogRef: MatDialogRef<ReconcileActionComponent>,
        @Inject(MAT_DIALOG_DATA) public data: any,
        public functions: FunctionsService
    ) { }

    ngOnInit(): void {
        this.checkReconcile();
    }

    onSubmit() {
        this.loading = true;

        this.executeAction();
    }

    checkReconcile() {
        this.resourcesErrors = [];

        return new Promise((resolve, reject) => {
            this.http.post('../rest/resourcesList/users/' + this.data.userId + '/groups/' + this.data.groupId + '/baskets/' + this.data.basketId + '/actions/' + this.data.action.id + '/checkReconcile', { resources: this.data.resIds,  })
            .subscribe((data: any) => {
                if(!this.functions.empty(data.resourcesInformations.error)) {
                    this.resourcesErrors = data.resourcesInformations.error;
                }
                if (data.resourcesInformations.success) {
                    data.resourcesInformations.success.forEach((value: any) => {
                        this.selectedRes.push(value.res_id);
                    });
                }
                this.noResourceToProcess = this.resourcesErrors.length === this.data.resIds.length;
                resolve(true);
            }, (err: any) => {
                this.notify.handleSoftErrors(err);
                this.dialogRef.close();
            });
        });
    }

    executeAction() {
        
        this.http.put(this.data.processActionRoute, { resources: this.selectedRes, data: {resId : this.appSearchAdvList.getSelectedRessources()[0]} }).pipe(
            tap((data: any) => {
                if (data !== null && !this.functions.empty(data.errors)) {
                    this.notify.error(data.errors);
                } else {
                    this.dialogRef.close(this.selectedRes);
                }
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    launchSearch(value: any) {
        this.searchUrl = value;
        this.appSearchAdvList.refreshDao(value);
    }
}
