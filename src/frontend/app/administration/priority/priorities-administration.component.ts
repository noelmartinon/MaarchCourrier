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
import {FunctionsService} from "../../../service/functions.service";

declare function $j(selector: any): any;

@Component({
    templateUrl: "priorities-administration.component.html",
    providers: [NotificationService, AppService]
})
export class PrioritiesAdministrationComponent implements OnInit {
    /*HEADER*/
    @ViewChild('snav', { static: true }) public  sidenavLeft   : MatSidenav;
    @ViewChild('snav2', { static: true }) public sidenavRight  : MatSidenav;

    lang            : any       = LANG;
    loading         : boolean   = false;

    priorities      : any[]     = [];
    prioritiesOrder : any[]     = [];
    dataSource      : any;
    displayedColumns            = ['id', 'label', 'delays', 'actions'];


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
        this.headerService.setHeader(this.lang.administration + ' ' + this.lang.priorities);
        window['MainHeaderComponent'].setSnav(this.sidenavLeft);
        window['MainHeaderComponent'].setSnavRight(null);

        this.loading = true;

        this.http.get('../../rest/priorities')
            .subscribe((data: any) => {
                this.priorities = data["priorities"];
                this.loading = false;
                this.http.get("../../rest/sortedPriorities")
                    .subscribe((data: any) => {
                        this.prioritiesOrder = data['priorities'];
                    }, () => {
                        location.href = "index.php";
                    });
                setTimeout(() => {
                    this.dataSource = new MatTableDataSource(this.priorities);
                    this.dataSource.paginator = this.paginator;
                    this.dataSource.sortingDataAccessor = this.functions.listSortingDataAccessor;
                    this.sort.active = 'label';
                    this.sort.direction = 'asc';
                    this.dataSource.sort = this.sort;
                }, 0);
            }, () => {
                location.href = "index.php";
            })
    }

    deletePriority(id: string) {
        let r = confirm(this.lang.deleteMsg);

        if (r) {
            this.http.delete("../../rest/priorities/" + id)
                .subscribe((data: any) => {
                    this.priorities = data["priorities"];
                    this.dataSource = new MatTableDataSource(this.priorities);
                    this.dataSource.paginator = this.paginator;
                    this.dataSource.sort = this.sort;
                    this.notify.success(this.lang.priorityDeleted);
                }, (err) => {
                    this.notify.error(err.error.errors);
                })
        }
    }

    updatePrioritiesOrder() {
        this.http.put("../../rest/sortedPriorities", this.prioritiesOrder)
            .subscribe((data: any) => {
                this.prioritiesOrder = data['priorities'];
                this.notify.success(this.lang.modificationSaved);
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }
}
