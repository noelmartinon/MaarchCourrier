import { Component, OnInit, ViewChild, TemplateRef, ViewContainerRef } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../notification.service';
import { HeaderService } from '../../../service/header.service';
import { MatPaginator } from '@angular/material/paginator';
import { MatSort } from '@angular/material/sort';
import { MatTableDataSource } from '@angular/material/table';
import { AppService } from '../../../service/app.service';
import { FunctionsService } from '../../../service/functions.service';

@Component({
    templateUrl: 'shippings-administration.component.html',
    providers: [AppService]
})
export class ShippingsAdministrationComponent implements OnInit {

    @ViewChild('adminMenuTemplate', { static: true }) adminMenuTemplate: TemplateRef<any>;

    lang: any = LANG;

    shippings: any[] = [];

    loading: boolean = false;

    displayedColumns = ['label', 'description', 'accountid', 'actions'];
    dataSource: any;
    @ViewChild(MatPaginator, { static: false }) paginator: MatPaginator;
    @ViewChild(MatSort, { static: false }) sort: MatSort;


    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        private headerService: HeaderService,
        public appService: AppService,
        public functions: FunctionsService,
        private viewContainerRef: ViewContainerRef
    ) { }

    applyFilter(filterValue: string) {
        filterValue = filterValue.trim(); // Remove whitespace
        filterValue = filterValue.toLowerCase(); // MatTableDataSource defaults to lowercase matches
        this.dataSource.filter = filterValue;
        this.dataSource.filterPredicate = (template: any, filter: string) => {
            return this.functions.filterUnSensitive(template, filter, ['label', 'description', 'accountid']);
        };
    }

    ngOnInit(): void {
        this.headerService.setHeader(this.lang.administration + ' ' + this.lang.shippings);

        this.headerService.injectInSideBarLeft(this.adminMenuTemplate, this.viewContainerRef, 'adminMenu');

        this.loading = true;

        this.http.get('../rest/administration/shippings')
            .subscribe((data: any) => {
                this.shippings = data.shippings;

                setTimeout(() => {
                    this.dataSource = new MatTableDataSource(this.shippings);
                    this.dataSource.paginator = this.paginator;
                    this.dataSource.sort = this.sort;
                }, 0);
                this.loading = false;
            });
    }

    deleteShipping(id: number) {
        const r = confirm(this.lang.deleteMsg);

        if (r) {
            this.http.delete('../rest/administration/shippings/' + id)
                .subscribe((data: any) => {
                    this.shippings = data.shippings;
                    this.dataSource = new MatTableDataSource(this.shippings);
                    this.dataSource.paginator = this.paginator;
                    this.dataSource.sort = this.sort;
                    this.notify.success(this.lang.shippingDeleted);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }
}
