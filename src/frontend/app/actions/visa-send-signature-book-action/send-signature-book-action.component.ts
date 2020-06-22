import { Component, OnInit, Inject, ViewChild, AfterViewInit } from '@angular/core';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../../service/notification/notification.service';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { HttpClient } from '@angular/common/http';
import { NoteEditorComponent } from '../../notes/note-editor.component';
import { tap, finalize, catchError } from 'rxjs/operators';
import { of } from 'rxjs';
import { FunctionsService } from '../../../service/functions.service';
import { VisaWorkflowComponent } from '../../visa/visa-workflow.component';

@Component({
    templateUrl: "send-signature-book-action.component.html",
    styleUrls: ['send-signature-book-action.component.scss'],
})
export class SendSignatureBookActionComponent implements AfterViewInit {

    lang: any = LANG;
    loading: boolean = true;

    resourcesMailing: any[] = [];
    resourcesError: any[] = [];

    noResourceToProcess: boolean = null;

    integrationsInfo: any = {
        inSignatureBook: {
            icon: 'fas fa-file-signature'
        }
    };

    @ViewChild('noteEditor', { static: true }) noteEditor: NoteEditorComponent;
    @ViewChild('appVisaWorkflow', { static: false }) appVisaWorkflow: VisaWorkflowComponent;

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        public dialogRef: MatDialogRef<SendSignatureBookActionComponent>,
        @Inject(MAT_DIALOG_DATA) public data: any,
        public functions: FunctionsService) { }

    async ngAfterViewInit(): Promise<void> {
        if (this.data.resIds.length === 0 && !this.functions.empty(this.data.resource.destination)) {
            await this.appVisaWorkflow.loadListModel(this.data.resource.destination);
            this.loading = false;
        } else if (this.data.resIds.length > 0) {
            await this.checkSignatureBook();
            this.loading = false;
        } else {
            this.loading = false;
        }
        if (this.data.resIds.length === 1) {
            await this.appVisaWorkflow.loadWorkflow(this.data.resIds[0]);
            if (this.appVisaWorkflow.emptyWorkflow()) {
                this.appVisaWorkflow.loadDefaultWorkflow(this.data.resIds[0]);
            }
        }
    }

    async onSubmit() {
        this.loading = true;

        if (this.data.resIds.length === 0) {
            let res = await this.indexDocument();
            if (res) {
                res = await this.appVisaWorkflow.saveVisaWorkflow(this.data.resIds);
            }
            if (res) {
                this.executeIndexingAction(this.data.resIds[0]);
            }
        } else {
            const realResSelected: number[] = this.data.resIds.filter((resId: any) => this.resourcesError.map(resErr => resErr.res_id).indexOf(resId) === -1);

            const res = await this.appVisaWorkflow.saveVisaWorkflow(realResSelected);

            if (res) {
                this.executeAction(realResSelected);
            }
        }
        this.loading = false;
    }

    checkSignatureBook() {
        this.resourcesError = [];

        return new Promise((resolve, reject) => {
            this.http.post('../rest/resourcesList/users/' + this.data.userId + '/groups/' + this.data.groupId + '/baskets/' + this.data.basketId + '/actions/' + this.data.action.id + '/checkSignatureBook', { resources: this.data.resIds })
                .subscribe((data: any) => {
                    if (!this.functions.empty(data.resourcesInformations.error)) {
                        this.resourcesError = data.resourcesInformations.error;
                    }
                    this.noResourceToProcess = this.data.resIds.length === this.resourcesError.length;
                    if (data.resourcesInformations.success) {
                        this.resourcesMailing = data.resourcesInformations.success.filter((element: any) => element.mailing);
                    }
                    resolve(true);
                }, (err: any) => {
                    this.notify.handleSoftErrors(err);
                    this.dialogRef.close();
                });
        });
    }

    toggleIntegration(integrationId: string) {
        this.http.put(`../rest/resourcesList/integrations`, {resources : this.data.resIds, integrations : { [integrationId] : !this.data.resource.integrations[integrationId]}}).pipe(
            tap(() => {
                this.data.resource.integrations[integrationId] = !this.data.resource.integrations[integrationId];
                this.checkSignatureBook();
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    indexDocument() {
        this.data.resource['integrations'] = {
            inSignatureBook : true
        };

        return new Promise((resolve, reject) => {
            this.http.post('../rest/resources', this.data.resource).pipe(
                tap((data: any) => {
                    this.data.resIds = [data.resId];
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    resolve(false);
                    return of(false);
                })
            ).subscribe();
        });
    }

    executeAction(realResSelected: number[]) {
        this.http.put(this.data.processActionRoute, { resources: realResSelected, note: this.noteEditor.getNote() }).pipe(
            tap((data: any) => {
                if (!data) {
                    this.dialogRef.close(realResSelected);
                }
                if (data && data.errors != null) {
                    this.notify.error(data.errors);
                }
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    executeIndexingAction(resId: number) {

        this.http.put(this.data.indexActionRoute, { resource: resId, note: this.noteEditor.getNote() }).pipe(
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
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    isValidAction() {
        return !this.noResourceToProcess && this.appVisaWorkflow !== undefined && !this.appVisaWorkflow.emptyWorkflow() && !this.appVisaWorkflow.workflowEnd();
    }
}
