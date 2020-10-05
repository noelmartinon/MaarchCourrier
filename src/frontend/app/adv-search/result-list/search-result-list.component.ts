import { Component, OnInit, ViewChild, EventEmitter, ViewContainerRef, OnDestroy, TemplateRef, Input } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import { MatDialog, MatDialogRef } from '@angular/material/dialog';
import { MatPaginator } from '@angular/material/paginator';
import { MatSidenav } from '@angular/material/sidenav';
import { MatSort } from '@angular/material/sort';
import { DomSanitizer, SafeHtml } from '@angular/platform-browser';
import { startWith, switchMap, map, catchError, takeUntil, tap } from 'rxjs/operators';
import { ActivatedRoute, Router } from '@angular/router';
import { HeaderService } from '@service/header.service';
import { Overlay } from '@angular/cdk/overlay';
import { PanelListComponent } from '@appRoot/list/panel/panel-list.component';
import { AppService } from '@service/app.service';
import { BasketHomeComponent } from '@appRoot/basket/basket-home.component';
import { FolderActionListComponent } from '@appRoot/folder/folder-action-list/folder-action-list.component';
import { FoldersService } from '@appRoot/folder/folders.service';
import { FunctionsService } from '@service/functions.service';
import { of, merge, Subject, Subscription, Observable } from 'rxjs';
import { CriteriaToolComponent } from '@appRoot/adv-search/criteria-tool/criteria-tool.component';
import { IndexingFieldsService } from '@service/indexing-fields.service';
import { CriteriaSearchService } from '@service/criteriaSearch.service';
import { HighlightPipe } from '@plugins/highlight.pipe';

declare var $: any;

@Component({
    selector: 'app-search-result-list',
    templateUrl: 'search-result-list.component.html',
    styleUrls: ['search-result-list.component.scss'],
    providers: [HighlightPipe]
})
export class SearchResultListComponent implements OnInit, OnDestroy {

    @Input() searchTerm: string = '';
    @Input() actionMode: boolean = true;
    @Input() singleSelection: boolean = false;
    @Input() standalone: boolean = false;
    @Input() hideFilter: boolean = false;
    @Input() appCriteriaTool: CriteriaToolComponent;
    @Input() sidenavRight: MatSidenav;

    loading: boolean = true;
    initSearch: boolean = false;
    docUrl: string = '';
    public innerHtml: SafeHtml;
    searchUrl: string = '../rest/search';
    // searchTerm: string = '';
    criteria: any = {};
    homeData: any;

    injectDatasParam = {
        resId: 0,
        editable: false
    };
    currentResource: any = {};

    filtersChange = new EventEmitter();

    dragInit: boolean = true;

    dialogRef: MatDialogRef<any>;

    displayedColumnsBasket: string[] = ['resId'];

    displayedMainData: any = [
        {
            'value': 'chrono',
            'cssClasses': ['softColorData', 'align_centerData', 'chronoData'],
            'icon': ''
        },
        {
            'value': 'subject',
            'cssClasses': ['longData'],
            'icon': ''
        }
    ];

    resultListDatabase: ResultListHttpDao | null;
    data: any = [];
    resultsLength = 0;
    isLoadingResults = true;
    listProperties: any = {};
    currentChrono: string = '';
    currentMode: string = '';

    thumbnailUrl: string = '';

    selectedRes: Array<number> = [];
    allResInBasket: number[] = [];
    selectedDiffusionTab: number = 0;
    folderInfo: any = {
        id: 0,
        'label': '',
        'ownerDisplayName': '',
        'entitiesSharing': []
    };
    folderInfoOpened: boolean = false;

    private destroy$ = new Subject<boolean>();
    subscription: Subscription;

    displayColsOrder = [
        { 'id': 'destUser' },
        { 'id': 'categoryId' },
        { 'id': 'creationDate' },
        { 'id': 'processLimitDate' },
        { 'id': 'entityLabel' },
        { 'id': 'subject' },
        { 'id': 'chrono' },
        { 'id': 'priority' },
        { 'id': 'status' },
        { 'id': 'typeLabel' }
    ];

    @ViewChild('filterTemplate', { static: true }) filterTemplate: TemplateRef<any>;
    @ViewChild('toolTemplate', { static: true }) toolTemplate: TemplateRef<any>;
    @ViewChild('panelTemplate', { static: true }) panelTemplate: TemplateRef<any>;
    @ViewChild('adminMenuTemplate', { static: true }) adminMenuTemplate: TemplateRef<any>;
    @ViewChild('actionsListContext', { static: false }) actionsList: FolderActionListComponent;
    @ViewChild('appPanelList', { static: false }) appPanelList: PanelListComponent;
    // @ViewChild('appCriteriaTool', { static: true }) appCriteriaTool: CriteriaToolComponent;

    currentSelectedChrono: string = '';

    @ViewChild(MatPaginator, { static: true }) paginator: MatPaginator;
    @ViewChild('tableBasketListSort', { static: true }) sort: MatSort;
    @ViewChild('basketHome', { static: true }) basketHome: BasketHomeComponent;

    constructor(
        private _activatedRoute: ActivatedRoute,
        public translate: TranslateService,
        private router: Router,
        private route: ActivatedRoute,
        public http: HttpClient,
        public dialog: MatDialog,
        private sanitizer: DomSanitizer,
        private headerService: HeaderService,
        public criteriaSearchService: CriteriaSearchService,
        private notify: NotificationService,
        public overlay: Overlay,
        public viewContainerRef: ViewContainerRef,
        public appService: AppService,
        public foldersService: FoldersService,
        public functions: FunctionsService,
        public indexingFieldService: IndexingFieldsService,
        public highlightPipe: HighlightPipe,
    ) {
        _activatedRoute.queryParams.subscribe(
            params => {
                if (!this.functions.empty(params.value)) {
                    this.searchTerm = params.value;
                    this.initSearch = true;
                    this.criteria = {
                        meta: {
                            values: this.searchTerm
                        }
                    };
                }
            }
        );
    }

    ngOnInit(): void {
        if (!this.functions.empty(this.searchTerm)) {
            this.initSearch = true;
            this.criteria = {
                meta: {
                    values: this.searchTerm
                }
            };
        }
        this.headerService.sideBarAdmin = true;

        this.isLoadingResults = false;

        if (this.toolTemplate !== undefined) {
            this.headerService.initTemplate(this.toolTemplate, this.viewContainerRef, 'toolTemplate');
        }

        if (this.panelTemplate !== undefined && this.sidenavRight !== undefined) {
            this.headerService.initTemplate(this.panelTemplate, this.viewContainerRef, 'panelTemplate');
        }

        if (this.filterTemplate !== undefined && !this.hideFilter) {
            this.headerService.initTemplate(this.filterTemplate, this.viewContainerRef, 'filterTemplate');
        }

        this.listProperties = this.criteriaSearchService.initListsProperties(this.headerService.user.id);

        this.loading = false;
    }

    initSavedCriteria() {
        if (Object.keys(this.listProperties.criteria).length > 0) {
            const obj = { query: [] };
            Object.keys(this.listProperties.criteria).forEach(key => {
                const objectItem = {};
                objectItem['identifier'] = key;
                objectItem['values'] = this.listProperties.criteria[key].values;
                obj.query.push(objectItem);
            });
            this.appCriteriaTool.selectSearchTemplate(obj, false);
            this.criteria = this.listProperties.criteria;
            this.initResultList();
        } else if (this.initSearch) {
            this.initResultList();
        } else {
            this.appCriteriaTool.toggleTool(true);
        }
    }

    ngOnDestroy() {
        this.destroy$.next(true);
    }

    launch(row: any) {
        const thisSelect = { checked: true };
        const thisDeselect = { checked: false };

        if (this.actionMode) {
            row.checked = true;
            this.toggleAllRes(thisDeselect);
            this.toggleRes(thisSelect, row);
            this.router.navigate([`/resources/${row.resId}`]);
        } else {
            row.checked = !row.checked;
            this.toggleRes(row.checked ? thisSelect : thisDeselect, row);
        }
    }

    launchSearch(criteria: any) {
        this.criteria = JSON.parse(JSON.stringify(criteria));
        if (!this.initSearch) {
            this.initResultList();
            this.initSearch = true;
        } else {
            this.refreshDao();
        }
        this.appCriteriaTool.toggleTool(false);
    }

    initResultList() {
        this.resultListDatabase = new ResultListHttpDao(this.http, this.criteriaSearchService);
        // If the user changes the sort order, reset back to the first page.
        this.paginator.pageIndex = this.listProperties.page;
        this.paginator.pageSize = this.listProperties.pageSize;
        this.sort.sortChange.subscribe(() => this.paginator.pageIndex = 0);

        // When list is refresh (sort, page, filters)
        merge(this.sort.sortChange, this.paginator.page, this.filtersChange)
            .pipe(
                takeUntil(this.destroy$),
                startWith({}),
                switchMap(() => {
                    this.isLoadingResults = true;
                    return this.resultListDatabase!.getRepoIssues(
                        this.sort.active, this.sort.direction, this.paginator.pageIndex, this.searchUrl, this.listProperties, this.paginator.pageSize, this.criteria);
                }),
                map((data: any) => {
                    // Flip flag to show that loading has finished.
                    this.isLoadingResults = false;
                    data = this.processPostData(data);
                    this.resultsLength = data.count;
                    this.allResInBasket = data.allResources;
                    // this.headerService.setHeader('Dossier : ' + this.folderInfo.label);
                    return data.resources;
                }),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    // this.router.navigate(['/home']);
                    this.isLoadingResults = false;
                    return of(false);
                })
            ).subscribe(data => this.data = data);
    }

    goTo(row: any) {
        // this.criteriaSearchService.filterMode = false;
        if (this.docUrl === '../rest/resources/' + row.resId + '/content' && this.sidenavRight.opened) {
            this.sidenavRight.close();
        } else {
            this.docUrl = '../rest/resources/' + row.resId + '/content';
            this.currentChrono = row.chrono;
            this.innerHtml = this.sanitizer.bypassSecurityTrustHtml(
                '<iframe style=\'height:100%;width:100%;\' src=\'' + this.docUrl + '\' class=\'embed-responsive-item\'>' +
                '</iframe>');
            this.sidenavRight.open();
        }
    }

    goToDetail(row: any) {
        this.router.navigate([`/resources/${row.resId}`]);
    }

    goToFolder(folder: any) {
        this.router.navigate([`/folders/${folder.id}`]);
    }

    togglePanel(mode: string, row: any) {
        const thisSelect = { checked: true };
        const thisDeselect = { checked: false };
        row.checked = true;
        this.toggleAllRes(thisDeselect);
        this.toggleRes(thisSelect, row);

        if (this.currentResource.resId === row.resId && this.sidenavRight.opened && this.currentMode === mode) {
            this.sidenavRight.close();
        } else {
            this.currentMode = mode;
            this.currentResource = row;
            this.appPanelList.loadComponent(mode, row);
            this.sidenavRight.open();
        }
    }

    refreshBadgeNotes(nb: number) {
        this.currentResource.countNotes = nb;
    }

    refreshFolderInformations() {
        this.http.get('../rest/folders/' + this.folderInfo.id)
            .subscribe((data: any) => {
                const keywordEntities = [{
                    keyword: 'ALL_ENTITIES',
                    text: this.translate.instant('lang.allEntities'),
                }];
                this.folderInfo = {
                    'id': data.folder.id,
                    'label': data.folder.label,
                    'ownerDisplayName': data.folder.ownerDisplayName,
                    'entitiesSharing': data.folder.sharing.entities.map((entity: any) => {
                        if (!this.functions.empty(entity.label)) {
                            return entity.label;
                        } else {
                            return keywordEntities.filter((element: any) => element.keyword === entity.keyword)[0].text;
                        }
                    }),
                };
                this.headerService.setHeader(this.folderInfo.label, '', 'fa fa-folder-open');
            });
    }

    refreshBadgeAttachments(nb: number) {
        this.currentResource.countAttachments = nb;
    }

    refreshDao() {
        this.paginator.pageIndex = this.listProperties.page;
        this.filtersChange.emit();
    }

    refreshDaoAfterAction() {
        this.sidenavRight.close();
        this.refreshDao();
        const e: any = { checked: false };
        this.toggleAllRes(e);
    }

    viewThumbnail(row: any) {
        if (row.hasDocument) {
            this.thumbnailUrl = '../rest/resources/' + row.resId + '/thumbnail';
            $('#viewThumbnail').show();
            $('#listContent').css({ 'overflow': 'hidden' });
        }
    }

    closeThumbnail() {
        $('#viewThumbnail').hide();
        $('#listContent').css({ 'overflow': 'auto' });
    }

    processPostData(data: any) {
        console.log();


        data.resources.forEach((element: any) => {
            // Process main datas
            Object.keys(element).forEach((key) => {
                if (key === 'statusImage' && element[key] == null) {
                    element[key] = 'fa-question undefined';
                } else if ((element[key] == null || element[key] === '') && ['closingDate', 'countAttachments', 'countNotes', 'display', 'mailTracking', 'hasDocument'].indexOf(key) === -1) {
                    element[key] = this.translate.instant('lang.undefined');
                }
                if (Object.keys(this.criteria).indexOf(key) > -1) {
                    element[key] = this.highlightPipe.transform(element[key], this.criteria[key].values);
                }

                if (key === 'status' && !this.functions.empty(this.criteria[key])) {
                    element['inStatus'] = this.criteria[key].map((item: any) => item.label).indexOf(element[key]) > -1;
                }
            });
            // element['inNotes'] = true;
            // element['inNotes'] = true;
            // element['inAttachments'] = true;
            element['checked'] = this.selectedRes.indexOf(element['resId']) !== -1;
        });

        return data;
    }

    toggleRes(e: any, row: any) {
        if (this.singleSelection) {
            this.toggleAllRes({ checked: false });
        }
        if (e.checked) {
            if (this.selectedRes.indexOf(row.resId) === -1) {
                this.selectedRes.push(row.resId);
                row.checked = true;
            }
        } else {
            const index = this.selectedRes.indexOf(row.resId);
            this.selectedRes.splice(index, 1);
            row.checked = false;
        }
    }

    toggleAllRes(e: any) {
        this.selectedRes = [];
        if (e.checked) {
            this.data.forEach((element: any) => {
                element['checked'] = true;
            });
            this.selectedRes = JSON.parse(JSON.stringify(this.allResInBasket));
        } else {
            this.data.forEach((element: any) => {
                element['checked'] = false;
            });
        }
    }

    selectSpecificRes(row: any) {
        const thisSelect = { checked: true };
        const thisDeselect = { checked: false };

        this.toggleAllRes(thisDeselect);
        this.toggleRes(thisSelect, row);
    }

    open({ x, y }: MouseEvent, row: any) {
        const thisSelect = { checked: true };
        const thisDeselect = { checked: false };
        if (row.checked === false) {
            row.checked = true;
            this.toggleAllRes(thisDeselect);
            this.toggleRes(thisSelect, row);
        }
        if (this.actionMode) {
            this.actionsList.open(x, y, row);
        }

        // prevents default
        return false;
    }

    listTodrag() {
        return this.foldersService.getDragIds();
    }

    toggleMailTracking(row: any) {
        if (!row.mailTracking) {
            this.http.post('../rest/resources/follow', { resources: [row.resId] }).pipe(
                tap(() => {
                    this.headerService.nbResourcesFollowed++;
                    row.mailTracking = !row.mailTracking;
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        } else {
            this.http.request('DELETE', '../rest/resources/unfollow', { body: { resources: [row.resId] } }).pipe(
                tap(() => {
                    this.headerService.nbResourcesFollowed--;
                    row.mailTracking = !row.mailTracking;
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        }
    }

    viewDocument(row: any) {
        this.http.get(`../rest/resources/${row.resId}/content?mode=view`, { responseType: 'blob' }).pipe(
            tap((data: any) => {
                const file = new Blob([data], { type: 'application/pdf' });
                const fileURL = URL.createObjectURL(file);
                const newWindow = window.open();
                newWindow.document.write(`<iframe style="width: 100%;height: 100%;margin: 0;padding: 0;" src="${fileURL}" frameborder="0" allowfullscreen></iframe>`);
                newWindow.document.title = row.chrono;
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    emptyCriteria() {
        return Object.keys(this.criteria).length === 0;
    }

    isArrayType(value: any) {
        return (Array.isArray(value));
    }

    removeCriteria(identifier: string, value: any = null) {
        if (identifier !== '_ALL') {
            const tmpArrCrit = [];
            if (value === null || this.criteria[identifier].values.length === 1) {
                this.criteria[identifier].values = [];
            } else {
                const indexArr = this.criteria[identifier].values.indexOf(value);
                this.criteria[identifier].values.splice(indexArr, 1);
            }
        } else {
            Object.keys(this.criteria).forEach(key => {
                this.criteria[key].values = [];
            });
        }
        this.appCriteriaTool.refreshCriteria(this.criteria);
    }

    updateFilters() {
        this.listProperties.page = 0;

        this.criteriaSearchService.updateListsProperties(this.listProperties);

        this.refreshDao();
    }

    changeOrderDir() {
        if (this.listProperties.orderDir === 'ASC') {
            this.listProperties.orderDir = 'DESC';
        } else {
            this.listProperties.orderDir = 'ASC';
        }
        this.updateFilters();
    }

    getSelectedResources() {
        return this.selectedRes;
    }
}
export interface BasketList {
    folder: any;
    resources: any[];
    countResources: number;
    allResources: number[];
}

export class ResultListHttpDao {

    constructor(private http: HttpClient, private criteriaSearchService: CriteriaSearchService) { }

    getRepoIssues(sort: string, order: string, page: number, href: string, filters: any, pageSize: number, criteria: any): Observable<BasketList> {
        this.criteriaSearchService.updateListsPropertiesPage(page);
        this.criteriaSearchService.updateListsPropertiesPageSize(pageSize);
        this.criteriaSearchService.updateListsPropertiesCriteria(criteria);
        const offset = page * pageSize;
        const requestUrl = `${href}?limit=${pageSize}&offset=${offset}&order=${filters.order}&orderDir=${filters.orderDir}`;
        return this.http.post<BasketList>(requestUrl, this.criteriaSearchService.formatDatas(JSON.parse(JSON.stringify(criteria))));
    }
}
