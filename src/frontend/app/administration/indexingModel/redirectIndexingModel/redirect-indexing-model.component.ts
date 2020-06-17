import {Component, Inject, OnInit} from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import {LANG} from '../../../translate.component';
import {catchError, finalize, map, tap} from 'rxjs/operators';
import {of} from 'rxjs/internal/observable/of';
import {HttpClient} from '@angular/common/http';
import {NotificationService} from '../../../notification.service';

@Component({
    templateUrl: 'redirect-indexing-model.component.html',
    styleUrls: ['redirect-indexing-model.component.scss']
})
export class RedirectIndexingModelComponent implements OnInit  {

    lang: any = LANG;

    indexingModels: any[] = [];
    modelIds: any[] = [];

    selectedModelId: any;
    selectedModelFields: any[];

    mainIndexingModel: any;
    mainIndexingModelFields: any[];

    resetFields: any[] = [];

    statuses: any[] = [];

    loading: boolean = false;

    constructor(@Inject(MAT_DIALOG_DATA) public data: any,
                public dialogRef: MatDialogRef<RedirectIndexingModelComponent>,
                public http: HttpClient,
                private notify: NotificationService,
                ) {
        console.log(data);

        this.mainIndexingModel = data.indexingModel;
    }

    ngOnInit(): void {
        this.loadIndexingModels();

        this.loadIndexingModelFields();

        this.loadStatuses();
    }

    loadIndexingModels() {
        this.http.get('../rest/indexingModels').pipe(
            map((data: any) => {
                return data.indexingModels.filter((info: any) => info.private === false);
            }),
            tap((data: any) => {
                this.indexingModels = data;
                this.modelIds = this.indexingModels.map(model => model.id);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    loadIndexingModelFields() {
        this.http.get('../rest/indexingModels/' + this.mainIndexingModel.id).pipe(
            tap((data: any) => {
                this.mainIndexingModelFields = data.indexingModel.fields;
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    loadStatuses() {
        this.http.get('../rest/statuses').pipe(
            tap((data: any) => {
                this.statuses = data.statuses;
                console.log(this.statuses);

                this.mainIndexingModel.used.forEach((element: any) => {
                    const elementStatus = this.statuses.find(status => status.id === element.status);
                    if (elementStatus !== undefined) {
                        element.status = elementStatus.label_status;
                    }
                });
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    changeModel(event: any) {
        this.selectedModelId = event.value;
        this.http.get('../rest/indexingModels/' + this.selectedModelId).pipe(
            tap((data: any) => {
                this.selectedModelFields = data.indexingModel.fields;
                console.log(this.selectedModelFields);
                this.resetFields = this.mainIndexingModelFields.filter(field =>
                    this.selectedModelFields.find(selectedField => selectedField.identifier === field.identifier) === undefined);
                console.log(this.resetFields);
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    onSubmit() {
        console.log('selected : ' + this.selectedModelId);
        this.http.request('DELETE', '../rest/indexingModels/' + this.mainIndexingModel.id, {body: {targetId: this.selectedModelId}}).pipe(
            tap(() => {
                this.notify.success(this.lang.indexingModelDeleted);
                this.dialogRef.close('ok');
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }
}
