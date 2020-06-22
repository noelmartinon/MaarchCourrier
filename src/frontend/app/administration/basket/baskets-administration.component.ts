import { Component, OnInit, ViewChild, ViewContainerRef, TemplateRef } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { MatPaginator } from '@angular/material/paginator';
import { MatSidenav } from '@angular/material/sidenav';
import { MatSort } from '@angular/material/sort';
import { MatTableDataSource } from '@angular/material/table';
import { NotificationService } from '../../../service/notification/notification.service';
import { HeaderService } from '../../../service/header.service';
import { AppService } from '../../../service/app.service';
import { FunctionsService } from '../../../service/functions.service';

@Component({
    templateUrl: 'baskets-administration.component.html'
})
export class BasketsAdministrationComponent implements OnInit {

    @ViewChild('snav2', { static: true }) public sidenavRight: MatSidenav;
    @ViewChild('adminMenuTemplate', { static: true }) adminMenuTemplate: TemplateRef<any>;

    lang: any = LANG;
    loading: boolean = false;

    baskets: any[] = [];
    basketsOrder: any[] = [];

    displayedColumns = ['basket_id', 'basket_name', 'basket_desc', 'actions'];
    dataSource: any;

    @ViewChild(MatPaginator, { static: false }) paginator: MatPaginator;
    @ViewChild(MatSort, { static: false }) sort: MatSort;
    applyFilter(filterValue: string) {
        filterValue = filterValue.trim(); // Remove whitespace
        filterValue = filterValue.toLowerCase(); // MatTableDataSource defaults to lowercase matches
        this.dataSource.filter = filterValue;
        this.dataSource.filterPredicate = (template: any, filter: string) => {
            return this.functions.filterUnSensitive(template, filter, ['basket_id', 'basket_name', 'basket_desc']);
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
        this.headerService.injectInSideBarLeft(this.adminMenuTemplate, this.viewContainerRef, 'adminMenu');
        this.headerService.setHeader(this.lang.administration + ' ' + this.lang.baskets);

        this.loading = true;

        this.http.get('../rest/baskets')
            .subscribe((data: any) => {
                this.baskets = data['baskets'];
                this.loading = false;
                setTimeout(() => {
                    this.http.get('../rest/sortedBaskets')
                        .subscribe((dataSort: any) => {
                            this.basketsOrder = dataSort['baskets'];
                        }, (err) => {
                            this.notify.handleErrors(err);
                        });
                    this.dataSource = new MatTableDataSource(this.baskets);
                    this.dataSource.paginator = this.paginator;
                    this.dataSource.sortingDataAccessor = this.functions.listSortingDataAccessor;
                    this.sort.active = 'basket_id';
                    this.sort.direction = 'asc';
                    this.dataSource.sort = this.sort;
                }, 0);
            }, (err) => {
                this.notify.handleErrors(err);
            });
    }

    delete(basket: any) {
        const r = confirm(this.lang.confirmAction + ' ' + this.lang.delete + ' « ' + basket['basket_name'] + ' »');

        if (r) {
            this.http.delete('../rest/baskets/' + basket['basket_id'])
                .subscribe((data: any) => {
                    this.notify.success(this.lang.basketDeleted);
                    this.baskets = data['baskets'];
                    this.dataSource = new MatTableDataSource(this.baskets);
                    this.dataSource.paginator = this.paginator;
                    this.dataSource.sort = this.sort;
                    this.http.get('../rest/sortedBaskets')
                        .subscribe((dataSort: any) => {
                            this.basketsOrder = dataSort['baskets'];
                        }, (err) => {
                            this.notify.handleErrors(err);
                        });
                }, (err: any) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

    updateBasketOrder(currentBasket: any) {
        this.http.put('../rest/sortedBaskets/' + currentBasket.basket_id, this.basketsOrder)
            .subscribe((data: any) => {
                this.baskets = data['baskets'];
                this.notify.success(this.lang.modificationSaved);
            }, (err: any) => {
                this.notify.error(err.error.errors);
            });
    }
}
