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
import { FormControl, Validators } from '@angular/forms';
import { catchError, debounceTime, filter, map, tap } from 'rxjs/operators';
import { of } from 'rxjs';

@Component({
    templateUrl: 'shippings-administration.component.html',
    styleUrls: ['shippings-administration.component.scss']
})
export class ShippingsAdministrationComponent implements OnInit {

    @ViewChild('adminMenuTemplate', { static: true }) adminMenuTemplate: TemplateRef<any>;

    shippingConf: any = {
        enabled: new FormControl(false),
        authUri: new FormControl('https://connect.maileva.com', [Validators.required]),
        uri: new FormControl('https://api.maileva.com', [Validators.required]),
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
        this.http.get(`../rest/configurations/admin_shippings`).pipe(
            map((data: any) => data.configuration.value),
            tap((data: any) => {
                Object.keys(this.shippingConf).forEach(elemId => {
                    if (!this.functions.empty(data)) {
                        this.shippingConf[elemId].setValue(data[elemId]);
                    }
                    this.shippingConf[elemId].valueChanges
                        .pipe(
                            debounceTime(1000),
                            filter(() => this.shippingConf['authUri'].valid && this.shippingConf['uri'].valid),
                            tap(() => {
                                this.saveConfiguration();
                            }),
                        ).subscribe();
                });
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    saveConfiguration() {
        this.http.put(`../rest/configurations/admin_shippings`, this.formatConfiguration()).pipe(
            tap(() => {
                this.notify.success(this.translate.instant('lang.dataUpdated'));
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
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
