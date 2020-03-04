import { Component, OnInit, ViewChild } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { MatPaginator } from '@angular/material/paginator';
import { MatSidenav } from '@angular/material/sidenav';
import { MatSort } from '@angular/material/sort';
import { MatTableDataSource } from '@angular/material/table';
import { NotificationService } from '../../notification.service';
import { HeaderService }        from '../../../service/header.service';
import { AppService } from '../../../service/app.service';
import {FunctionsService} from "../../../service/functions.service";

declare function $j(selector: any): any;

@Component({
    templateUrl: "baskets-administration.component.html",
    providers: [NotificationService, AppService]
})
export class BasketsAdministrationComponent implements OnInit {

    @ViewChild('snav', { static: true }) public  sidenavLeft   : MatSidenav;
    @ViewChild('snav2', { static: true }) public sidenavRight  : MatSidenav;

    lang                            : any       = LANG;
    loading                         : boolean   = false;

    baskets                         : any[]     = [];
    basketsOrder                    : any[]     = [];

    displayedColumns    = ['basket_id', 'basket_name', 'basket_desc', 'actions'];
    dataSource          : any;

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
        public functions: FunctionsService
        ) {
            $j("link[href='merged_css.php']").remove();
    }

    ngOnInit(): void {
        this.headerService.setHeader(this.lang.administration + ' ' + this.lang.baskets);
        window['MainHeaderComponent'].setSnav(this.sidenavLeft);
        window['MainHeaderComponent'].setSnavRight(null);

        this.loading = true;

        this.http.get("../../rest/baskets")
            .subscribe((data: any) => {
                this.baskets = data['baskets'];
                this.loading = false;
                setTimeout(() => {
                    this.http.get("../../rest/sortedBaskets")
                        .subscribe((data: any) => {
                            this.basketsOrder = data['baskets'];
                        }, () => {
                            location.href = "index.php";
                        });
                    this.dataSource = new MatTableDataSource(this.baskets);
                    this.dataSource.paginator = this.paginator;
                    this.dataSource.sortingDataAccessor = this.functions.listSortingDataAccessor;
                    this.sort.active = 'basket_id';
                    this.sort.direction = 'asc';
                    this.dataSource.sort = this.sort;
                }, 0);
            }, () => {
                location.href = "index.php";
            });
    }

    delete(basket: any) {
        let r = confirm(this.lang.confirmAction + ' ' + this.lang.delete + ' « ' + basket['basket_name'] + ' »');

        if (r) {
            this.http.delete("../../rest/baskets/" + basket['basket_id'])
                .subscribe((data: any) => {
                    this.notify.success(this.lang.basketDeleted);
                    this.baskets = data['baskets'];
                    this.dataSource = new MatTableDataSource(this.baskets);
                    this.dataSource.paginator = this.paginator;
                    this.dataSource.sort = this.sort;
                    this.http.get("../../rest/sortedBaskets")
                        .subscribe((data: any) => {
                            this.basketsOrder = data['baskets'];
                        }, () => {
                            location.href = "index.php";
                        });
                }, (err: any) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

    updateBasketOrder(currentBasket: any) {
        this.http.put("../../rest/sortedBaskets/" + currentBasket.basket_id, this.basketsOrder)
            .subscribe((data: any) => {
                this.baskets = data['baskets'];
                this.notify.success(this.lang.modificationSaved);
            }, (err: any) => {
                this.notify.error(err.error.errors);
            });
    }
}
