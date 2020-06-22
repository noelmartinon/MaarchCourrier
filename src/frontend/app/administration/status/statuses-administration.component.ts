import { Component, OnInit, ViewChild, TemplateRef, ViewContainerRef } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { MatPaginator } from '@angular/material/paginator';
import { MatSort } from '@angular/material/sort';
import { MatTableDataSource } from '@angular/material/table';
import { NotificationService } from '../../../service/notification/notification.service';
import { HeaderService } from '../../../service/header.service';
import { AppService } from '../../../service/app.service';
import { FunctionsService } from '../../../service/functions.service';

@Component({
    templateUrl: 'statuses-administration.component.html'
})
export class StatusesAdministrationComponent implements OnInit {

    @ViewChild('adminMenuTemplate', { static: true }) adminMenuTemplate: TemplateRef<any>;

    lang: any = LANG;
    loading: boolean = false;

    statuses: Status[] = [];

    displayedColumns = ['img_filename', 'id', 'label_status', 'identifier'];
    dataSource = new MatTableDataSource(this.statuses);

    @ViewChild(MatPaginator, { static: false }) paginator: MatPaginator;
    @ViewChild(MatSort, { static: false }) sort: MatSort;
    applyFilter(filterValue: string) {
        filterValue = filterValue.trim(); // Remove whitespace
        filterValue = filterValue.toLowerCase(); // MatTableDataSource defaults to lowercase matches
        this.dataSource.filter = filterValue;
        this.dataSource.filterPredicate = (template, filter: string) => {
            return this.functions.filterUnSensitive(template, filter, ['id', 'label_status']);
        };
    }

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        private headerService: HeaderService,
        public appService: AppService,
        public functions: FunctionsService,
        private viewContainerRef: ViewContainerRef
    ) { }

    ngOnInit(): void {
        this.headerService.setHeader(this.lang.administration + ' ' + this.lang.statuses);

        this.headerService.injectInSideBarLeft(this.adminMenuTemplate, this.viewContainerRef, 'adminMenu');

        this.loading = true;

        this.http.get('../rest/statuses')
            .subscribe((data: any) => {
                this.statuses = data.statuses;
                this.loading = false;
                setTimeout(() => {
                    this.dataSource = new MatTableDataSource(this.statuses);
                    this.dataSource.paginator = this.paginator;
                    this.dataSource.sortingDataAccessor = this.functions.listSortingDataAccessor;
                    this.sort.active = 'label_status';
                    this.sort.direction = 'asc';
                    this.dataSource.sort = this.sort;
                }, 0);

            }, (err: any) => {
                this.notify.error(err.error.errors);
            });
    }

    deleteStatus(status: any) {
        const resp = confirm(this.lang.confirmAction + ' ' + this.lang.delete + ' « ' + status.id + ' »');
        if (resp) {
            this.http.delete('../rest/statuses/' + status.identifier)
                .subscribe((data: any) => {
                    this.statuses = data.statuses;
                    this.dataSource = new MatTableDataSource(this.statuses);
                    this.dataSource.paginator = this.paginator;
                    this.dataSource.sort = this.sort;
                    this.notify.success(this.lang.statusDeleted);

                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

}

export interface Status {
    id: string;
    can_be_modified: string;
    can_be_searchead: string;
    identifier: number;
    img_filename: string;
    is_system: string;
    label_status: string;
    maarch_module: string;
}
