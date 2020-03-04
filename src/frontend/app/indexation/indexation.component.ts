import { Component, OnInit, ViewChild, ViewContainerRef } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../translate.component';
import { NotificationService } from '../notification.service';
import { MatDialog, MatDialogRef } from '@angular/material/dialog';
import { MatSidenav } from '@angular/material/sidenav';

import { ActivatedRoute, Router, ParamMap } from '@angular/router';
import { HeaderService } from '../../service/header.service';
import { FiltersListService } from '../../service/filtersList.service';

import { Overlay } from '@angular/cdk/overlay';
import { AppService } from '../../service/app.service';
import { IndexingFormComponent } from './indexing-form/indexing-form.component';
import { tap, finalize, catchError, map, filter, exhaustMap } from 'rxjs/operators';
import { of, Subscription } from 'rxjs';
import { DocumentViewerComponent } from '../viewer/document-viewer.component';
import { ConfirmComponent } from '../../plugins/modal/confirm.component';
import { AddPrivateIndexingModelModalComponent } from './private-indexing-model/add-private-indexing-model-modal.component';
import { ActionsService } from '../actions/actions.service';
import { SortPipe } from '../../plugins/sorting.pipe';
import { FunctionsService } from '../../service/functions.service';

@Component({
    templateUrl: "indexation.component.html",
    styleUrls: [
        'indexation.component.scss',
        'indexing-form/indexing-form.component.scss'
    ],
    providers: [AppService, ActionsService, SortPipe],
})
export class IndexationComponent implements OnInit {

    lang: any = LANG;

    loading: boolean = false;

    @ViewChild('snav', { static: true }) sidenavLeft: MatSidenav;
    @ViewChild('snav2', { static: true }) sidenavRight: MatSidenav;

    @ViewChild('indexingForm', { static: false }) indexingForm: IndexingFormComponent;
    @ViewChild('appDocumentViewer', { static: false }) appDocumentViewer: DocumentViewerComponent;

    indexingModels: any[] = [];
    currentIndexingModel: any = {};
    currentGroupId: number;

    actionsList: any[] = [];
    selectedAction: any = {
        id: 0,
        label: '',
        component: '',
        default: false,
        categoryUse: []
    };
    tmpFilename: string = '';

    dialogRef: MatDialogRef<any>;

    subscription: Subscription;

    isMailing: boolean = false;

    constructor(
        private route: ActivatedRoute,
        private _activatedRoute: ActivatedRoute,
        public http: HttpClient,
        public dialog: MatDialog,
        private headerService: HeaderService,
        public filtersListService: FiltersListService,
        private notify: NotificationService,
        public overlay: Overlay,
        public viewContainerRef: ViewContainerRef,
        public appService: AppService,
        public actionService: ActionsService,
        private router: Router,
        private sortPipe: SortPipe,
        public functions: FunctionsService
    ) {

        _activatedRoute.queryParams.subscribe(
            params => this.tmpFilename = params.tmpfilename
        );

        // Event after process action 
        this.subscription = this.actionService.catchAction().subscribe(resIds => {
            const param = this.isMailing ? {
                isMailing : true
            } : null;         
            this.router.navigate([`/resources/${resIds[0]}`], { queryParams: param });
        });
    }

    ngOnInit(): void {
        // Use to clean data after navigate on same url
        this._activatedRoute.queryParamMap.subscribe((paramMap: ParamMap) => {
            const refresh = paramMap.get('refresh');
            if (refresh) {
                this.appDocumentViewer.templateListForm.reset();
                this.appDocumentViewer.file = {
                    name: '',
                    type: '',
                    content: null,
                    src: null
                };
                this.appDocumentViewer.triggerEvent.emit('cleanFile');
                this.loadIndexingModel(this.indexingModels[0]);
            }
        });

        this.fetchData();
    }

    fetchData() {
        this.loading = false;
        this.headerService.setHeader("Enregistrement d'un courrier");

        this.route.params.subscribe(params => {
            this.currentGroupId = params['groupId'];
            this.http.get("../../rest/indexingModels").pipe(
                tap((data: any) => {
                    this.indexingModels = data.indexingModels;
                    if (this.indexingModels.length > 0) {
                        this.currentIndexingModel = this.indexingModels.filter(model => model.default === true)[0];
                        if (this.currentIndexingModel === undefined) {
                            this.currentIndexingModel = this.indexingModels[0];
                            this.notify.error(this.lang.noDefaultIndexingModel);
                        }
                        this.loadIndexingModelsList();
                    }

                    if (this.appService.getViewMode()) {
                        setTimeout(() => {
                            this.sidenavLeft.open();
                        }, 400);
                    }
                }),
                finalize(() => this.loading = false),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
            this.http.get("../../rest/indexing/groups/" + this.currentGroupId + "/actions").pipe(
                map((data: any) => {
                    data.actions = data.actions.map((action: any, index: number) => {
                        return {
                            id: action.id,
                            label: action.label,
                            component: action.component,
                            enabled: action.enabled,
                            default: index === 0 ? true : false,
                            categoryUse: action.categories
                        }
                    });
                    return data;
                }),
                tap((data: any) => {
                    this.selectedAction = data.actions[0];
                    this.actionsList = data.actions;
                }),
                finalize(() => this.loading = false),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        },
            (err: any) => {
                this.notify.handleErrors(err);
            });
    }

    loadIndexingModelsList() {
        let tmpIndexingModels: any[] = this.sortPipe.transform(this.indexingModels.filter(elem => elem.master === null), 'label');
        let privateTmpIndexingModels: any[] = this.sortPipe.transform(this.indexingModels.filter(elem => elem.master !== null), 'label');
        this.indexingModels = [];
        tmpIndexingModels.forEach(indexingModel => {
            this.indexingModels.push(indexingModel);
            privateTmpIndexingModels.forEach(privateIndexingModel => {
                if (privateIndexingModel.master === indexingModel.id) {
                    this.indexingModels.push(privateIndexingModel);
                }
            });
        });
    }

    onSubmit() {
        if (this.indexingForm.isValidForm()) {
            const formatdatas = this.formatDatas(this.indexingForm.getDatas());
            
            formatdatas['modelId'] = this.currentIndexingModel.master !== null ? this.currentIndexingModel.master : this.currentIndexingModel.id;
            formatdatas['chrono'] = true;

            this.appDocumentViewer.getFile().pipe(
                tap((data: any) => {
                    formatdatas['encodedFile'] = data.content;
                    formatdatas['format'] = data.format;

                    this.isMailing = !this.functions.empty(formatdatas.recipients) && formatdatas.recipients.length > 0 && this.currentIndexingModel.category === 'outgoing' && formatdatas['encodedFile'] === null;

                    if (formatdatas['encodedFile'] === null) {
                        this.dialogRef = this.dialog.open(ConfirmComponent, { autoFocus: false, disableClose: true, data: { title: this.lang.noFile, msg: this.lang.noFileMsg } });

                        this.dialogRef.afterClosed().pipe(
                            filter((data: string) => data === 'ok'),
                            tap(() => {
                                this.actionService.launchIndexingAction(this.selectedAction, this.headerService.user.id, this.currentGroupId, formatdatas);
                            }),
                            catchError((err: any) => {
                                this.notify.handleErrors(err);
                                return of(false);
                            })
                        ).subscribe();
                    } else {
                        this.actionService.launchIndexingAction(this.selectedAction, this.headerService.user.id, this.currentGroupId, formatdatas);
                    }
                })
            ).subscribe();
        } else {
            this.notify.error(this.lang.mustFixErrors);
        }
    }

    formatDatas(datas: any) {
        let formatData: any = {};
        const regex = /indexingCustomField_[.]*/g;

        formatData['customFields'] = {};

        datas.forEach((element: any) => {

            if (element.identifier.match(regex) !== null) {

                formatData['customFields'][element.identifier.split('_')[1]] = element.default_value;

            } else {
                formatData[element.identifier] = element.default_value;
            }
        });
        return formatData;
    }

    loadIndexingModel(indexingModel: any) {
        this.currentIndexingModel = indexingModel;
        this.indexingForm.loadForm(indexingModel.id);
    }

    selectAction(action: any) {
        this.selectedAction = action;
    }

    savePrivateIndexingModel() {
        let fields = JSON.parse(JSON.stringify(this.indexingForm.getDatas()));
        fields.forEach((element: any, key: any) => {
            delete fields[key].event;
            delete fields[key].label;
            delete fields[key].system;
            delete fields[key].type;
            delete fields[key].values;
        });

        const privateIndexingModel = {
            category: this.indexingForm.getCategory(),
            label: '',
            owner: this.headerService.user.id,
            private: true,
            fields: fields,
            master: this.currentIndexingModel.master !== null ? this.currentIndexingModel.master : this.currentIndexingModel.id
        }

        const masterIndexingModel = this.indexingModels.filter((indexingModel) => indexingModel.id === privateIndexingModel.master)[0];
        this.dialogRef = this.dialog.open(AddPrivateIndexingModelModalComponent, { autoFocus: true, disableClose: true, data: { indexingModel: privateIndexingModel, masterIndexingModel: masterIndexingModel } });

        this.dialogRef.afterClosed().pipe(
            filter((data: any) => data !== undefined),
            tap((data) => {
                this.indexingModels.push(data.indexingModel);
                this.currentIndexingModel = this.indexingModels.filter(indexingModel => indexingModel.id === data.indexingModel.id)[0];
                this.loadIndexingModelsList();
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    deletePrivateIndexingModel(id: number, index: number) {
        this.dialogRef = this.dialog.open(ConfirmComponent, { autoFocus: false, disableClose: true, data: { title: this.lang.delete, msg: this.lang.confirmAction } });

        this.dialogRef.afterClosed().pipe(
            filter((data: string) => data === 'ok'),
            exhaustMap(() => this.http.delete(`../../rest/indexingModels/${id}`)),
            tap(() => {
                this.indexingModels.splice(index, 1);
                this.notify.success(this.lang.indexingModelDeleted);
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    ngOnDestroy() {
        // unsubscribe to ensure no memory leaks
        this.subscription.unsubscribe();
    }

    showActionInCurrentCategory(action: any) {

        if (this.selectedAction.categoryUse.indexOf(this.indexingForm.getCategory()) === -1) {
            const newAction = this.actionsList.filter(action => action.categoryUse.indexOf(this.indexingForm.getCategory()) > -1)[0];
            if (newAction !== undefined) {
                this.selectedAction = this.actionsList.filter(action => action.categoryUse.indexOf(this.indexingForm.getCategory()) > -1)[0];
            } else {
                this.selectedAction = {
                    id: 0,
                    label: '',
                    component: '',
                    default: false,
                    categoryUse: []
                };
            }
        }
        if (action.categoryUse.indexOf(this.indexingForm.getCategory()) > -1) {
            return true;
        } else {
            return false;
        }
    }

    refreshDatas() {
        this.appDocumentViewer.setDatas(this.formatDatas(this.indexingForm.getDatas()));
    }

}
