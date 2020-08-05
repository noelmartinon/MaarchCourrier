import { Injectable } from '@angular/core';
import { LocalStorageService } from '../../service/local-storage.service';
import { HeaderService } from '../../service/header.service';
import { FunctionsService } from '../../service/functions.service';
import { MatTableDataSource } from '@angular/material/table';
import { MatPaginator } from '@angular/material/paginator';
import { MatSort } from '@angular/material/sort';
import { merge } from 'rxjs/internal/observable/merge';
import { startWith } from 'rxjs/internal/operators/startWith';
import { tap } from 'rxjs/internal/operators/tap';
import { catchError } from 'rxjs/internal/operators/catchError';
import { of } from 'rxjs/internal/observable/of';
import { NotificationService } from '../../service/notification/notification.service';
import { FormControl } from '@angular/forms';
import { debounceTime } from 'rxjs/operators';

@Injectable()
export class AdministrationService {
    filters: any = {};
    defaultFilters: any = {
        admin_users: {
            sort: 'user_id',
            sortDirection: 'asc',
            page: 0,
            field: ''
        },
        admin_actions: {
            sort: 'id',
            sortDirection: 'asc',
            page: 0,
            field: ''
        },
    };
    dataSource: MatTableDataSource<any>;
    filterColumns: string[];
    searchTerm: FormControl = new FormControl('');
    currentAdminId: string = '';

    constructor(
        private notify: NotificationService,
        public headerService: HeaderService,
        public functionsService: FunctionsService,
        private localStorage: LocalStorageService,
    ) {
        if (this.localStorage.get(`filtersAdmin_${this.headerService.user.id}`) !== null) {
            this.filters = JSON.parse(this.localStorage.get(`filtersAdmin_${this.headerService.user.id}`));
        }
    }

    setDataSource(adminId: string, data: any, sort: MatSort, paginator: MatPaginator, filterColumns: string[]) {
        this.currentAdminId = adminId;
        this.searchTerm = new FormControl('');

        this.searchTerm.valueChanges
            .pipe(
                // debounceTime(300),
                // filter(value => value.length > 2),
                tap((filterValue: any) => {
                    this.filters[this.currentAdminId]['field'] = filterValue;
                    this.setFilter(this.filters[this.currentAdminId]);
                    filterValue = filterValue.trim(); // Remove whitespace
                    filterValue = filterValue.toLowerCase(); // MatTableDataSource defaults to lowercase matches
                    setTimeout(() => {
                        this.dataSource.filter = filterValue;
                    }, 0);
                    this.dataSource.filterPredicate = (template, filter: string) => {
                        return this.functionsService.filterUnSensitive(template, filter, this.filterColumns);
                    };
                }),
            ).subscribe();
        this.filterColumns = filterColumns;
        this.dataSource = new MatTableDataSource(data);

        this.dataSource.paginator = paginator;
        this.dataSource.sortingDataAccessor = this.functionsService.listSortingDataAccessor;

        if (this.functionsService.empty(this.getFilter())) {

            this.setFilter(
                this.defaultFilters[this.currentAdminId]
            );
        }

        sort.active = this.getFilter('sort');
        sort.direction = this.getFilter('sortDirection');
        paginator.pageIndex = this.getFilter('page');

        this.dataSource.sort = sort;

        this.searchTerm.setValue(this.getFilter('field'));

        merge(sort.sortChange, paginator.page)
            .pipe(
                startWith({}),
                tap(() => {
                    this.setFilter(
                        {
                            sort: sort.active,
                            sortDirection: sort.direction,
                            page: paginator.pageIndex,
                            field: this.getFilter('field')
                        }
                    );
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
    }

    setFilter(filter: any) {
        this.filters[this.currentAdminId] = filter;
        this.localStorage.save(`filtersAdmin_${this.headerService.user.id}`, JSON.stringify(this.filters));
    }

    getFilterField() {
        return this.searchTerm;
    }

    getDataSource() {
        return this.dataSource;
    }

    getFilter(idFilter: string = null) {
        if (!this.functionsService.empty(this.filters[this.currentAdminId])) {
            if (!this.functionsService.empty(idFilter)) {
                return !this.functionsService.empty(this.filters[this.currentAdminId][idFilter]) ? this.filters[this.currentAdminId][idFilter] : '';
            } else {
                return !this.functionsService.empty(this.filters[this.currentAdminId]) ? this.filters[this.currentAdminId] : '';
            }
        } else {
            return null;
        }
    }
}
