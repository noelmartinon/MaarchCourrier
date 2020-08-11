import { Component, OnInit, ViewChild, ViewContainerRef, TemplateRef } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../translate.component';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '../../service/notification/notification.service';
import { MatDialog, MatDialogRef } from '@angular/material/dialog';

import { ActivatedRoute, Router } from '@angular/router';
import { HeaderService } from '../../service/header.service';
import { FiltersListService } from '../../service/filtersList.service';

import { Overlay } from '@angular/cdk/overlay';
import { AppService } from '../../service/app.service';
import { IndexingFormComponent } from './indexing-form/indexing-form.component';
import { tap, finalize, catchError, map, filter, take } from 'rxjs/operators';
import { DocumentViewerComponent } from '../viewer/document-viewer.component';
import { ConfirmComponent } from '../../plugins/modal/confirm.component';
import { ActionsService } from '../actions/actions.service';
import { SortPipe } from '../../plugins/sorting.pipe';
import { FunctionsService } from '../../service/functions.service';
import { Subscription } from 'rxjs/internal/Subscription';
import { of } from 'rxjs/internal/observable/of';
import { SelectIndexingModelComponent } from './select-indexing-model/select-indexing-model.component';

@Component({
    templateUrl: 'indexation.component.html',
    styleUrls: [
        'indexation.component.scss',
        'indexing-form/indexing-form.component.scss'
    ],
    providers: [ActionsService, SortPipe],
})
export class IndexationComponent implements OnInit {

    lang: any = LANG;

    loading: boolean = false;

    @ViewChild('adminMenuTemplate', { static: true }) adminMenuTemplate: TemplateRef<any>;

    @ViewChild('appSelectIndexingModel', { static: false }) appSelectIndexingModel: SelectIndexingModelComponent;
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
        private translate: TranslateService,
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
            if (this.selectedAction.component === 'closeAndIndexAction') {
                this.appDocumentViewer.templateListForm.reset();
                this.appDocumentViewer.file = {
                    name: '',
                    type: '',
                    content: null,
                    src: null
                };
                this.appDocumentViewer.triggerEvent.emit('cleanFile');
                this.appSelectIndexingModel.resetIndexingModel();
            } else {
                const param = this.isMailing ? {
                    isMailing: true
                } : null;
                this.router.navigate([`/resources/${resIds[0]}`], { queryParams: param });
            }
        });
    }

    ngOnInit(): void {
        this.headerService.injectInSideBarLeft(this.adminMenuTemplate, this.viewContainerRef, 'adminMenu', 'form');
        this.headerService.sideBarButton = { icon: 'fa fa-home', label: this.translate.instant('lang.backHome'), route: '/home' };

        this.fetchData();
    }

    fetchData() {
        this.loading = false;
        this.headerService.setHeader(this.translate.instant('lang.recordingMail'));

        this.route.params.subscribe(params => {
            this.currentGroupId = params['groupId'];

            this.http.get('../rest/indexing/groups/' + this.currentGroupId + '/actions').pipe(
                map((data: any) => {
                    data.actions = data.actions.map((action: any, index: number) => {
                        return {
                            id: action.id,
                            label: action.label,
                            component: action.component,
                            enabled: action.enabled,
                            default: index === 0 ? true : false,
                            categoryUse: action.categories
                        };
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

    isEmptyIndexingModels() {
        return this.appSelectIndexingModel !== undefined && this.appSelectIndexingModel.getIndexingModels().length === 0;
    }

    onSubmit() {
        if (this.indexingForm.isValidForm()) {
            this.actionService.loading = true;
            const formatdatas = this.formatDatas(this.indexingForm.getDatas());

            formatdatas['modelId'] = this.currentIndexingModel.master !== null ? this.currentIndexingModel.master : this.currentIndexingModel.id;
            formatdatas['chrono'] = true;

            this.appDocumentViewer.getFile().pipe(
                take(1),
                tap((data: any) => {
                    formatdatas['encodedFile'] = data.content;
                    formatdatas['format'] = data.format;

                    this.isMailing = !this.functions.empty(formatdatas.recipients) && formatdatas.recipients.length > 0 && this.currentIndexingModel.category === 'outgoing' && formatdatas['encodedFile'] === null;

                    if (formatdatas['encodedFile'] === null) {
                        this.dialogRef = this.dialog.open(
                            ConfirmComponent, {
                            panelClass: 'maarch-modal',
                            autoFocus: false,
                            disableClose: true,
                            data: {
                                title: this.translate.instant('lang.noFile'),
                                msg: this.translate.instant('lang.noFileMsg')
                            }
                        }
                        );

                        this.dialogRef.afterClosed().pipe(
                            tap((result: string) => {
                                if (result !== 'ok') {
                                    this.actionService.loading = false;
                                }
                            }),
                            filter((result: string) => result === 'ok'),
                            tap(() => {
                                this.actionService.launchIndexingAction(
                                    this.selectedAction,
                                    this.headerService.user.id,
                                    this.currentGroupId, formatdatas
                                );
                            }),
                            catchError((err: any) => {
                                this.notify.handleErrors(err);
                                return of(false);
                            })
                        ).subscribe();
                    } else {
                        this.actionService.launchIndexingAction(
                            this.selectedAction,
                            this.headerService.user.id,
                            this.currentGroupId, formatdatas
                        );
                    }
                })
            ).subscribe();
        } else {
            this.notify.error(this.translate.instant('lang.mustFixErrors'));
        }
    }

    formatDatas(datas: any) {
        const formatData: any = {};
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
    }

    selectAction(action: any) {
        this.selectedAction = action;
    }


    ngOnDestroy() {
        // unsubscribe to ensure no memory leaks
        this.subscription.unsubscribe();
    }

    showActionInCurrentCategory(action: any) {

        if (this.selectedAction.categoryUse.indexOf(this.indexingForm.getCategory()) === -1) {
            const newAction = this.actionsList.filter(actionList => actionList.categoryUse.indexOf(this.indexingForm.getCategory()) > -1)[0];
            if (newAction !== undefined) {
                this.selectedAction = this.actionsList.filter(actionList => actionList.categoryUse.indexOf(this.indexingForm.getCategory()) > -1)[0];
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
