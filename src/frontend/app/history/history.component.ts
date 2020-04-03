import { Component, OnInit, ViewChild, EventEmitter, ElementRef, Input } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../translate.component';
import { NotificationService } from '../notification.service';
import { HeaderService } from '../../service/header.service';
import { Observable, merge, Subject, of as observableOf, of } from 'rxjs';
import { MatDialog } from '@angular/material/dialog';
import { MatPaginator } from '@angular/material/paginator';
import { MatSort } from '@angular/material/sort';
import { takeUntil, startWith, switchMap, map, catchError, filter, exhaustMap, tap, debounceTime, distinctUntilChanged, finalize } from 'rxjs/operators';
import { FormControl } from '@angular/forms';
import { FunctionsService } from '../../service/functions.service';
import { LatinisePipe } from 'ngx-pipes';
import { PrivilegeService } from '../../service/privileges.service';

@Component({
    selector: 'app-history-list',
    templateUrl: "history.component.html",
    styleUrls: ['history.component.scss'],
})
export class HistoryComponent implements OnInit {

    lang: any = LANG;
    loading: boolean = false;

    fullHistoryMode : boolean = true;
    
    filtersChange = new EventEmitter();

    data: any;

    displayedColumnsHistory: string[] = ['event_date', 'userLabel', 'info', 'remote_ip'];

    isLoadingResults = true;
    routeUrl: string = '../../rest/history';
    filterListUrl: string = '../../rest/history/availableFilters';
    extraParamUrl: string = '';
    resultListDatabase: HistoryListHttpDao | null;
    resultsLength = 0;

    searchHistory = new FormControl();
    startDateFilter: any = '';
    endDateFilter: any = '';
    filterUrl: string = '';
    filterList: any = null;
    filteredList: any = {};
    filterUsed: any = {};

    filterColor = {
        startDate: '#b5cfd8',
        endDate: '#7393a7',
        actions: '#7d5ba6',
        systemActions: '#7d5ba6',
        users: '#009dc5',
    };

    loadingFilters: boolean = true;

    @Input('resId') resId: number = null;

    @ViewChild(MatPaginator, { static: true }) paginator: MatPaginator;
    @ViewChild('tableHistoryListSort', { static: true }) sort: MatSort;
    @ViewChild('autoCompleteInput', { static: true }) autoCompleteInput: ElementRef;

    private destroy$ = new Subject<boolean>();

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        private headerService: HeaderService,
        public dialog: MatDialog,
        public functions: FunctionsService,
        private latinisePipe: LatinisePipe,
        public privilegeService: PrivilegeService) { }

    ngOnInit(): void {
        if (this.resId !== null) {
            this.displayedColumnsHistory = ['event_date', 'info'];
            this.fullHistoryMode = !this.privilegeService.hasCurrentUserPrivilege('view_doc_history')
        } else {
            this.displayedColumnsHistory = ['event_date', 'record_id', 'userLabel', 'info', 'remote_ip'];
        }
        this.loading = true;
        this.initHistoryMode();
        this.initHistoryList();
    }

    switchHistoryMode() {
        this.fullHistoryMode = !this.fullHistoryMode;
        this.initHistoryMode();
        this.refreshDao();
    }

    resetFilter() {
        this.loadingFilters = true;
        this.filterList = null;
        this.filterUsed = {};
        this.filterUrl = '';
    }

    initHistoryMode() {
        this.resetFilter();

        if (this.fullHistoryMode) {
            this.extraParamUrl = this.resId !== null ? `&resId=${this.resId}` : '';
            this.filterListUrl = this.resId !== null ? `../../rest/history/availableFilters?resId=${this.resId}` : '../../rest/history/availableFilters';
        } else {
            this.extraParamUrl = this.resId !== null ? `&resId=${this.resId}&onlyActions=true` : '&onlyActions=true';
            this.filterListUrl = this.resId !== null ? `../../rest/history/availableFilters?resId=${this.resId}&onlyActions=true` : '../../rest/history/availableFilters?onlyActions=true';
        }
    }

    initHistoryList() {
        this.resultListDatabase = new HistoryListHttpDao(this.http);
        this.paginator.pageIndex = 0;
        this.sort.active = 'event_date';
        this.sort.direction = 'desc';
        this.sort.sortChange.subscribe(() => this.paginator.pageIndex = 0);

        // When list is refresh (sort, page, filters)
        merge(this.sort.sortChange, this.paginator.page, this.filtersChange)
            .pipe(
                takeUntil(this.destroy$),
                startWith({}),
                switchMap(() => {
                    this.isLoadingResults = true;
                    return this.resultListDatabase!.getRepoIssues(
                        this.sort.active, this.sort.direction, this.paginator.pageIndex, this.routeUrl, this.filterUrl, this.extraParamUrl);
                }),
                map(data => {
                    this.isLoadingResults = false;
                    data = this.processPostData(data);
                    this.resultsLength = data.count;
                    return data.history;
                }),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    this.isLoadingResults = false;
                    return observableOf([]);
                })
            ).subscribe(data => this.data = data);
    }

    processPostData(data: any) {
        data.history = data.history.map((item: any) => {
            return {
                ...item,
                userLabel : !this.functions.empty(item.userLabel) ? item.userLabel : this.lang.userDeleted
            }
        })
        return data;
    }


    refreshDao() {
        this.paginator.pageIndex = 0;
        this.filtersChange.emit();
    }

    initFilterListHistory() {

        if (this.filterList === null) {
            this.filterList = {};
            this.filterUsed = {};
            this.filterUrl = '';
            this.loadingFilters = true;
            this.http.get(this.filterListUrl).pipe(
                map((data: any) => {
                    let deletedActions = data.actions.filter((action: any) => action.label === null).map((action: any) => action.id);
                    let deletedUser = data.users.filter((user: any) => user.label === null).map((user: any) => user.login);

                    data.actions = data.actions.filter((action: any) => action.label !== null);
                    if (deletedActions.length > 0) {
                        data.actions.push({
                            id: deletedActions,
                            label: this.lang.actionDeleted
                        });
                    }

                    data.users = data.users.filter((user: any) => user.label !== null);

                    if (deletedUser.length > 0) {
                        data.users.push({
                            id: deletedUser,
                            label: this.lang.userDeleted
                        });
                    }

                    data.systemActions = data.systemActions.map((syst: any) => {
                        return {
                            id: syst.id,
                            label: !this.functions.empty(this.lang[syst.id]) ? this.lang[syst.id] : syst.id
                        }
                    });
                    return data;
                }),
                tap((data: any) => {
                    Object.keys(data).forEach((filterType: any) => {
                        if (this.functions.empty(this.filterList[filterType])) {
                            this.filterList[filterType] = [];
                            this.filteredList[filterType] = [];
                        }
                        data[filterType].forEach((element: any) => {
                            this.filterList[filterType].push(element);
                        });

                        this.filteredList[filterType] = this.searchHistory.valueChanges
                            .pipe(
                                startWith(''),
                                map(element => element ? this.filter(element, filterType) : this.filterList[filterType].slice())
                            );
                    });

                }),
                finalize(() => this.loadingFilters = false),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();

        }
    }

    filterStartDate() {
        if (this.functions.empty(this.filterUsed['startDate'])) {
            this.filterUsed['startDate'] = [];
        }
        this.filterUsed['startDate'][0] = {
            id: this.functions.empty(this.startDateFilter) ? '' : this.functions.formatDateObjectToDateString(this.startDateFilter),
            label: this.functions.empty(this.startDateFilter) ? '' : this.functions.formatDateObjectToDateString(this.startDateFilter)
        };
        this.generateUrlFilter();
        this.refreshDao();
    }

    filterEndDate() {
        if (this.functions.empty(this.filterUsed['endDate'])) {
            this.filterUsed['endDate'] = [];
        }
        this.filterUsed['endDate'][0] = {
            id: this.functions.empty(this.endDateFilter) ? '' : this.functions.formatDateObjectToDateString(this.endDateFilter, true),
            label: this.functions.empty(this.endDateFilter) ? '' : this.functions.formatDateObjectToDateString(this.endDateFilter)
        };
        this.generateUrlFilter();
        this.refreshDao();
    }

    addItemFilter(elem: any) {
        elem.value.used = true;
        if (this.functions.empty(this.filterUsed[elem.id])) {
            this.filterUsed[elem.id] = [];
        }
        this.filterUsed[elem.id].push(elem.value);
        this.generateUrlFilter();
        this.searchHistory.reset();
        this.autoCompleteInput.nativeElement.blur();
        this.refreshDao();
    }

    removeItemFilter(elem: any, type: string, index: number) {
        elem.used = false;
        this.filterUsed[type].splice(index, 1);
        this.generateUrlFilter();
        this.refreshDao();
    }


    generateUrlFilter() {
        this.filterUrl = '';
        let arrTmpUrl: any[] = [];
        Object.keys(this.filterUsed).forEach((type: any) => {
            this.filterUsed[type].forEach((filter: any) => {
                if (!this.functions.empty(filter.id)) {
                    if (['startDate', 'endDate'].indexOf(type) > -1) {
                        arrTmpUrl.push(`${type}=${filter.id}`);
                    } else {
                        arrTmpUrl.push(`${type}[]=${filter.id}`);
                    }
                }
            });
        });
        if (arrTmpUrl.length > 0) {
            this.filterUrl = '&' + arrTmpUrl.join('&');
        }
    }

    private filter(value: string, type: string): any[] {
        if (typeof value === 'string') {
            const filterValue = this.latinisePipe.transform(value.toLowerCase());
            return this.filterList[type].filter((elem: any) => this.latinisePipe.transform(elem.label.toLowerCase()).includes(filterValue));
        } else {
            return this.filterList[type];
        }
    }
}

export interface HistoryList {
    history: any[];
    count: number;
}
export class HistoryListHttpDao {

    constructor(private http: HttpClient) { }

    getRepoIssues(sort: string, order: string, page: number, href: string, search: string, extraParamUrl: string): Observable<HistoryList> {

        let offset = page * 10;
        const requestUrl = `${href}?limit=10&offset=${offset}&order=${order}&orderBy=${sort}${search}${extraParamUrl}`;

        return this.http.get<HistoryList>(requestUrl);
    }
}