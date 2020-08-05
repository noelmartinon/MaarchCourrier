import { Component, ViewChild, OnInit, TemplateRef, ViewContainerRef } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../../service/notification/notification.service';
import { MatPaginator } from '@angular/material/paginator';
import { MatSidenav } from '@angular/material/sidenav';
import { MatSort } from '@angular/material/sort';
import { HeaderService } from '../../../service/header.service';
import { AppService } from '../../../service/app.service';
import { FunctionsService } from '../../../service/functions.service';
import { AdministrationService } from '../administration.service';

@Component({
    templateUrl: 'actions-administration.component.html'
})

export class ActionsAdministrationComponent implements OnInit {

    @ViewChild('snav2', { static: true }) public sidenavRight: MatSidenav;
    @ViewChild('adminMenuTemplate', { static: true }) adminMenuTemplate: TemplateRef<any>;

    lang: any = LANG;
    search: string = null;

    actions: any[] = [];
    titles: any[] = [];

    loading: boolean = false;

    displayedColumns = ['id', 'label_action', 'history', 'actions'];
    filterColumns = ['id', 'label_action'];

    @ViewChild(MatPaginator, { static: false }) paginator: MatPaginator;
    @ViewChild(MatSort, { static: false }) sort: MatSort;

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        private headerService: HeaderService,
        public appService: AppService,
        public adminService: AdministrationService,
        public functions: FunctionsService,
        private viewContainerRef: ViewContainerRef
    ) { }

    ngOnInit(): void {
        this.headerService.injectInSideBarLeft(this.adminMenuTemplate, this.viewContainerRef, 'adminMenu');

        this.loading = true;

        this.http.get('../rest/actions')
            .subscribe((data) => {
                this.actions = data['actions'];
                this.headerService.setHeader(this.lang.administration + ' ' + this.lang.actions);
                this.loading = false;
                setTimeout(() => {
                    this.adminService.setDataSource('admin_actions', this.actions, this.sort, this.paginator, this.filterColumns);
                }, 0);
            }, (err) => {
                this.notify.handleErrors(err);
            });
    }

    deleteAction(action: any) {
        const r = confirm(this.lang.confirmAction + ' ' + this.lang.delete + ' « ' + action.label_action + ' »');

        if (r) {
            this.http.delete('../rest/actions/' + action.id)
                .subscribe((data: any) => {
                    this.actions = data.actions;
                    this.adminService.setDataSource('admin_actions', this.actions, this.sort, this.paginator, this.filterColumns);
                    this.notify.success(this.lang.actionDeleted);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }
}
