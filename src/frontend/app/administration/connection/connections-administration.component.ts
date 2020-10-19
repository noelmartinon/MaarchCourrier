import { HttpClient } from '@angular/common/http';
import { Component, OnInit, TemplateRef, ViewChild, ViewContainerRef } from '@angular/core';
import { MatPaginator } from '@angular/material/paginator';
import { MatSort } from '@angular/material/sort';
import { TranslateService } from '@ngx-translate/core';
import { AppService } from '@service/app.service';
import { HeaderService } from '@service/header.service';
import { NotificationService } from '@service/notification/notification.service';
import { of } from 'rxjs';
import { catchError, tap } from 'rxjs/operators';
import { AdministrationService } from '../administration.service';

@Component({
    selector: 'app-connections-administration',
    templateUrl: './connections-administration.component.html',
    styleUrls: ['./connections-administration.component.scss']
})
export class ConnectionsAdministrationComponent implements OnInit {

    loading: boolean = true;

    @ViewChild('adminMenuTemplate', { static: true }) adminMenuTemplate: TemplateRef<any>;
    @ViewChild(MatPaginator, { static: false }) paginator: MatPaginator;
    @ViewChild(MatSort, { static: false }) sort: MatSort;

    subMenus: any[] = [
        {
            icon: 'fas fa-users-cog',
            route: '/administration/sso',
            label: this.translate.instant('lang.sso'),
            current: true
        }
    ];

    displayedColumns = ['id', 'label', 'actions'];
    filterColumns = ['id', 'label'];

    connections: any[] = [];

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        private notify: NotificationService,
        public appService: AppService,
        private headerService: HeaderService,
        private viewContainerRef: ViewContainerRef,
        public adminService: AdministrationService,
    ) { }

    ngOnInit(): void {
        this.headerService.injectInSideBarLeft(this.adminMenuTemplate, this.viewContainerRef, 'adminMenu');
        this.headerService.setHeader(this.translate.instant('lang.administration') + ' ' + this.translate.instant('lang.ssoConnections'));
        this.getConnections();
    }

    getConnections() {
        // FOR TEST
        this.connections = [
            {
                id: 1,
                label: 'Connexion SSO',
                enabled: true
            },
            {
                id: 1,
                label: 'Connexion SSO2',
                enabled: false
            }
        ];
        this.loading = false;
        setTimeout(() => {
            this.adminService.setDataSource('admin_groups', this.connections, this.sort, this.paginator, this.filterColumns);
        }, 0);

        /* this.http.get('../rest/???').pipe(
            tap((data: any) => {
                this.connections =  data;
                this.loading = false;
                setTimeout(() => {
                    this.adminService.setDataSource('admin_groups', this.connections, this.sort, this.paginator, this.filterColumns);
                }, 0);
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe(); */
    }

}
