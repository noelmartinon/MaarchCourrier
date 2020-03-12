import { Component, OnInit, ViewChild, TemplateRef, ViewContainerRef } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { AppService } from '../../../service/app.service';
import { FunctionsService } from '../../../service/functions.service';
import { HistoryComponent } from '../../history/history.component';
import { PrivilegeService } from '../../../service/privileges.service';
import { HeaderService } from '../../../service/header.service';

@Component({
    selector: 'admin-history',
    templateUrl: "history-administration.component.html",
    styleUrls: ['history-administration.component.scss'],
    providers: [AppService]
})
export class HistoryAdministrationComponent implements OnInit {

    @ViewChild('adminMenuTemplate', { static: true }) adminMenuTemplate: TemplateRef<any>;

    lang: any = LANG;

    startDateFilter: any = '';
    endDateFilter: any = '';

    @ViewChild('appHistoryList', { static: false }) appHistoryList: HistoryComponent;

    subMenus: any[] = [
        {
            icon: 'fa fa-history',
            route: '/administration/history',
            label: this.lang.history,
            current: true
        },
        {
            icon: 'fa fa-history',
            route: '/administration/history-batch',
            label: this.lang.historyBatch,
            current: false
        }
    ];

    constructor(
        public http: HttpClient,
        public appService: AppService,
        public functions: FunctionsService,
        private privilegeService: PrivilegeService,
        private headerService: HeaderService,
        private viewContainerRef: ViewContainerRef) { }

    ngOnInit(): void {
        this.headerService.setHeader(this.lang.administration + ' ' + this.lang.history.toLowerCase(), '', '');

        this.headerService.injectInSideBarLeft(this.adminMenuTemplate, this.viewContainerRef, 'adminMenu');
        
        if (this.privilegeService.hasCurrentUserPrivilege('view_history_batch')) {
            this.subMenus = [
                {
                    icon: 'fa fa-history',
                    route: '/administration/history',
                    label: this.lang.history,
                    current: true
                },
                {
                    icon: 'fa fa-history',
                    route: '/administration/history-batch',
                    label: this.lang.historyBatch,
                    current: false
                }
            ];
        } else {
            this.subMenus = [
                {
                    icon: 'fa fa-history',
                    route: '/administration/history',
                    label: this.lang.history,
                    current: true
                }
            ];
        }
    }
}