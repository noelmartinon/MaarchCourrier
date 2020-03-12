import { Component, OnInit, Inject, ViewChild } from '@angular/core';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../notification.service';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { HttpClient } from '@angular/common/http';
import { NoteEditorComponent } from '../../notes/note-editor.component';
import { tap, exhaustMap, finalize, catchError } from 'rxjs/operators';
import { of } from 'rxjs';
import { FunctionsService } from '../../../service/functions.service';
import {HeaderService} from "../../../service/header.service";

@Component({
    templateUrl: "close-mail-action.component.html",
    styleUrls: ['close-mail-action.component.scss'],
})
export class CloseMailActionComponent implements OnInit {

    lang: any = LANG;
    loading: boolean = false;

    @ViewChild('noteEditor', { static: false }) noteEditor: NoteEditorComponent;
    emptyMandatoryFields: Array<any> = [];
    canCloseResIds: Array<any> = [];
    mandatoryFields: any;
    customFields: Array<any> = [];
    requiredFields: any;

    constructor(
        public http: HttpClient, 
        private notify: NotificationService, 
        public dialogRef: MatDialogRef<CloseMailActionComponent>,
        @Inject(MAT_DIALOG_DATA) public data: any,
        public functions: FunctionsService,
        private headerService: HeaderService
    ) { }

    ngOnInit(): void { 
        if (this.data.resIds.length > 0) {
            this.loading = true;
            this.checkClose();
        } else {
            this.checkIndexingClose();
        }
    }

    checkIndexingClose() {
        this.http.get(`../../rest/actions/${this.data.action.id}`).pipe(
            tap((data: any) => {
                this.requiredFields = data.action.parameters.requiredFields;
            }),
            exhaustMap(() => this.http.get(`../../rest/customFields`)),
            tap((data: any) => this.customFields = data.customFields),
            tap(() => {
                let emptyFields: Array<any> = [];
                this.requiredFields.forEach((element: any) => {
                    for (let key of Object.keys(this.data.resource.customFields)) {
                        if (element == 'indexingCustomField_' + key && this.functions.empty(this.data.resource.customFields[key])) {
                            emptyFields.push(this.customFields.filter(elem => elem.id == key)[0].label);
                        }
                    }
                });
                if (!this.functions.empty(emptyFields)) {
                    this.emptyMandatoryFields.push({'fields': emptyFields.join(", ")});
                    this.canCloseResIds = [];
                } else {
                    this.canCloseResIds = [1];
                }
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe()
    }

    checkClose() {
        this.http.post(`../../rest/resourcesList/users/${this.data.userId}/groups/${this.data.groupId}/baskets/${this.data.basketId}/actions/${this.data.action.id}/checkCloseWithFieldsAction`, { resources: this.data.resIds }).pipe(
            tap((data: any) => {
                this.emptyMandatoryFields = data.errors;
                this.canCloseResIds = data.success;
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe()
    }

    onSubmit() {
        this.loading = true;
        if ( this.data.resIds.length === 0) {
            this.indexDocumentAndExecuteAction();
        } else {
            this.executeAction();
        }
    }

    indexDocumentAndExecuteAction() {
        
        this.http.post('../../rest/resources', this.data.resource).pipe(
            tap((data: any) => {
                this.data.resIds = [data.resId];
            }),
            exhaustMap(() => this.http.put(this.data.indexActionRoute, {resource : this.data.resIds[0], note : this.noteEditor.getNote()})),
            tap(() => {
                if (!this.functions.empty(this.data.resource['followed']) && this.data.resource['followed']) {
                    this.headerService.nbResourcesFollowed++;
                }
                this.dialogRef.close(this.data.resIds);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe()
    }

    executeAction() {
        this.http.put(this.data.processActionRoute, {resources : this.canCloseResIds, note : this.noteEditor.getNote()}).pipe(
            tap(() => {
                this.dialogRef.close(this.canCloseResIds);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }
}
