import { Component, OnInit, ViewChild, TemplateRef, ViewContainerRef } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import { HeaderService } from '@service/header.service';
import { MatPaginator } from '@angular/material/paginator';
import { MatSort } from '@angular/material/sort';
import { AppService } from '@service/app.service';
import { FunctionsService } from '@service/functions.service';
import { AdministrationService } from '../administration.service';
import { FormControl } from '@angular/forms';
import { debounceTime, tap } from 'rxjs/operators';

@Component({
    templateUrl: 'shippings-administration.component.html',
    styleUrls: ['shippings-administration.component.scss']
})
export class ShippingsAdministrationComponent implements OnInit {

    @ViewChild('adminMenuTemplate', { static: true }) adminMenuTemplate: TemplateRef<any>;

    shippingConf: any = {
        enabled: new FormControl(true),
        authUri: new FormControl('https://connect.maileva.com'),
        uri: new FormControl('https://api.maileva.com'),
    };

    shippings: any[] = [];

    loading: boolean = false;

    displayedColumns = ['label', 'description', 'accountid', 'actions'];
    filterColumns = ['label', 'description', 'accountid'];

    @ViewChild(MatPaginator, { static: false }) paginator: MatPaginator;
    @ViewChild(MatSort, { static: false }) sort: MatSort;


    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        private notify: NotificationService,
        private headerService: HeaderService,
        public appService: AppService,
        public functions: FunctionsService,
        public adminService: AdministrationService,
        private viewContainerRef: ViewContainerRef
    ) { }

    ngOnInit(): void {
        this.headerService.setHeader(this.translate.instant('lang.administration') + ' ' + this.translate.instant('lang.shippings'));

        this.headerService.injectInSideBarLeft(this.adminMenuTemplate, this.viewContainerRef, 'adminMenu');

        this.loading = true;

        this.initConfiguration();

        this.http.get('../rest/administration/shippings')
            .subscribe((data: any) => {
                this.shippings = data.shippings;

                setTimeout(() => {
                    this.adminService.setDataSource('admin_shippings', this.shippings, this.sort, this.paginator, this.filterColumns);
                }, 0);

                this.loading = false;
            });
    }

    initConfiguration() {
        Object.keys(this.shippingConf).forEach(elemId => {
            this.shippingConf[elemId].valueChanges
                .pipe(
                    debounceTime(300),
                    tap((value: any) => {
                        this.saveConfiguration();
                    }),
                ).subscribe();
        });
    }

    saveConfiguration() {
        console.log(this.formatConfiguration());

        /*this.http.put(`../rest/configurations/documentEditor`, this.formatEditorsConfig()).pipe(
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })*/
    }

    formatConfiguration() {
        const obj: any = {};
        Object.keys(this.shippingConf).forEach(elemId => {
            obj[elemId] = this.shippingConf[elemId].value;

        });
        return obj;
    }


    deleteShipping(id: number) {
        const r = confirm(this.translate.instant('lang.deleteMsg'));

        if (r) {
            this.http.delete('../rest/administration/shippings/' + id)
                .subscribe((data: any) => {
                    this.shippings = data.shippings;
                    this.adminService.setDataSource('admin_shippings', this.shippings, this.sort, this.paginator, this.filterColumns);
                    this.notify.success(this.translate.instant('lang.shippingDeleted'));
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }
}
