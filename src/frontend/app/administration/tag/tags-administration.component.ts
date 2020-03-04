import { Component, OnInit, ViewChild } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../notification.service';
import { MatPaginator } from '@angular/material/paginator';
import { MatSidenav } from '@angular/material/sidenav';
import { MatSort } from '@angular/material/sort';
import { MatTableDataSource } from '@angular/material/table';
import { HeaderService } from '../../../service/header.service';
import { AppService } from '../../../service/app.service';
import { tap, finalize, filter, exhaustMap, catchError } from 'rxjs/operators';
import { ConfirmComponent } from '../../../plugins/modal/confirm.component';
import { of } from 'rxjs';
import { MatDialog } from '@angular/material';
import {FunctionsService} from "../../../service/functions.service";

@Component({
    templateUrl: "tags-administration.component.html",
    providers: [AppService]
})
export class TagsAdministrationComponent implements OnInit {
    /*HEADER*/
    @ViewChild('snav', { static: true }) public sidenavLeft: MatSidenav;
    @ViewChild('snav2', { static: true }) public sidenavRight: MatSidenav;

    lang: any = LANG;
    loading: boolean = true;

    dataSource: any;
    resultsLength: number = 0;
    displayedColumns = ['label', 'actions'];


    @ViewChild(MatPaginator, { static: false }) paginator: MatPaginator;
    @ViewChild(MatSort, { static: false }) sort: MatSort;
    applyFilter(filterValue: string) {
        filterValue = filterValue.trim(); // Remove whitespace
        filterValue = filterValue.toLowerCase(); // MatTableDataSource defaults to lowercase matches
        this.dataSource.filter = filterValue;
    }

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        private headerService: HeaderService,
        public appService: AppService,
        public dialog: MatDialog,
        public functions: FunctionsService
    ) { }

    ngOnInit(): void {
        this.headerService.setHeader(this.lang.administration + ' ' + this.lang.tags);
        window['MainHeaderComponent'].setSnav(this.sidenavLeft);
        window['MainHeaderComponent'].setSnavRight(null);
        this.loadList();
    }

    loadList() {
        this.loading = true;
        this.http.get('../../rest/tags').pipe(
            tap((data: any) => {
                setTimeout(() => {
                    this.dataSource = new MatTableDataSource(data.tags);
                    this.resultsLength = data.tags.length;
                    this.dataSource.paginator = this.paginator;
                    this.dataSource.sortingDataAccessor = this.functions.listSortingDataAccessor;
                    this.sort.active = 'label';
                    this.sort.direction = 'asc';
                    this.dataSource.sort = this.sort;
                }, 0);

            }),
            finalize(() => this.loading = false)
        ).subscribe()
    }

    deleteTag(item: any) {
        const dialogRef = this.dialog.open(ConfirmComponent, { autoFocus: false, disableClose: true, data: { title: `${this.lang.delete} "${item.label}"`, msg: this.lang.confirmAction } });
        dialogRef.afterClosed().pipe(
            filter((data: string) => data === 'ok'),
            exhaustMap(() => this.http.delete(`../../rest/tags/${item.id}`)),
            tap(() => {
                this.loadList();
                this.notify.success(this.lang.tagDeleted);
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }
}
