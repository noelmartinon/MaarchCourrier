import { Component, OnInit, ViewChild, EventEmitter, Input } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '../../../service/notification/notification.service';
import { HeaderService }        from '../../../service/header.service';
import { AppService } from '../../../service/app.service';
import { Observable, merge, Subject, of as observableOf, of } from 'rxjs';
import { MatDialog } from '@angular/material/dialog';
import { MatPaginator } from '@angular/material/paginator';
import { MatSort } from '@angular/material/sort';
import { takeUntil, startWith, switchMap, map, catchError, filter, exhaustMap, tap, debounceTime, distinctUntilChanged } from 'rxjs/operators';
import { FormControl } from '@angular/forms';
import { FunctionsService } from '../../../service/functions.service';

@Component({
    selector: 'search-adv-list',
    templateUrl: "search-adv-list.component.html",
    styleUrls: ['search-adv-list.component.scss']
})
export class SearchAdvListComponent implements OnInit {

    lang: any = LANG;
    loading: boolean = false;

    filtersChange = new EventEmitter();
    
    data: any;

    displayedColumnsResource: string[] = ['action', 'category', 'chrono', 'status', 'subject', 'typeLabel', 'creationDate'];

    selectedRes: number[] = [];
    allResInSearch: number[] = [];

    isLoadingResults = true;
    routeUrl: string = '../rest/search';
    resultListDatabase: ResourceListHttpDao | null;
    resultsLength = 0;

    searchResource = new FormControl();

    @Input('search') search: string = '';
    @Input('singleMode') singleMode: boolean = false;
    @Input('excludeRes') excludeRes: number[] = [];

    

    @ViewChild(MatPaginator, { static: true }) paginator: MatPaginator;
    @ViewChild('tableResourceListSort', { static: true }) sort: MatSort;

    private destroy$ = new Subject<boolean>();
    
    constructor(
        private translate: TranslateService,
        public http: HttpClient, 
        private notify: NotificationService, 
        private headerService: HeaderService,
        public appService: AppService,
        public dialog: MatDialog,
        public functions: FunctionsService) { }

    ngOnInit(): void {
        this.loading = true;
        this.initResourceList();
        this.selectedRes = [];
    }

    initResourceList() {
        this.resultListDatabase = new ResourceListHttpDao(this.http);
        this.paginator.pageIndex = 0;
        this.sort.active = 'creationDate';
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
                        this.sort.active, this.sort.direction, this.paginator.pageIndex, this.routeUrl, this.search);
                }),
                map(data => {
                    this.isLoadingResults = false;
                    data = this.processPostData(data);
                    this.resultsLength = data.count;
                    this.allResInSearch = data.allResources;
                    return data.resources;
                }),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    this.isLoadingResults = false;
                    return observableOf([]);
                })
            ).subscribe(data => this.data = data);
    }

    processPostData(data: any) {

        data.resources.forEach((linkeRes: any) => {
            Object.keys(linkeRes).forEach((key) => {
                if (key == 'statusImage' && this.functions.empty(linkeRes[key])) {
                    linkeRes[key] = 'fa-question undefined';
                } else if (this.functions.empty(linkeRes[key]) && ['senders', 'recipients', 'attachments', 'hasDocument'].indexOf(key) === -1) {
                    linkeRes[key] = this.translate.instant('lang.undefined');
                }
            });
        });
        
        return data;
    }

    refreshDao(newUrl: string = null) {
        if (newUrl !== null) {
            this.search = newUrl;
        }
        this.filtersChange.emit();
    }

    toggleRes(e: any, row: any) {
        if (this.singleMode) {
            this.selectedRes = [];  
        }
        if (e.checked) {
            if (this.selectedRes.indexOf(row.resId) === -1) {
                this.selectedRes.push(row.resId);
                row.checked = true;
            }
        } else {
            let index = this.selectedRes.indexOf(row.resId);
            this.selectedRes.splice(index, 1);
            row.checked = false;
        }
    }

    toggleAllRes(e: any) {
        this.selectedRes = [];
        if (e.checked) {
            this.data.forEach((element: any) => {
                if (this.excludeRes.indexOf(element['resId']) === -1) {
                    element['checked'] = true;
                }
            });
            let selectResEnabled = this.allResInSearch.filter(elem => this.excludeRes.indexOf(elem) === -1)
            this.selectedRes = JSON.parse(JSON.stringify(selectResEnabled));
        } else {
            this.data.forEach((element: any) => {
                element['checked'] = false;
            });
        }
    }

    getSelectedRessources() {
        return this.selectedRes;
    }

}

export interface ResourceList {
    resources: any[];
    count: number;
    allResources : number[]
}
export class ResourceListHttpDao {

    constructor(private http: HttpClient) { }

    getRepoIssues(sort: string, order: string, page: number, href: string, search: string): Observable<ResourceList> {
        
        let offset = page * 10;
        const requestUrl = `${href}?limit=10&offset=${offset}&order=${order}&orderBy=${sort}${search}`;

        return this.http.get<ResourceList>(requestUrl);
    }
}
