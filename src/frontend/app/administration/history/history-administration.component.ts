import { Component, OnInit, ViewChild } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { MatSidenav } from '@angular/material/sidenav';
import { AppService } from '../../../service/app.service';
import { FunctionsService } from '../../../service/functions.service';
import { HistoryComponent } from '../../history/history.component';
import { tap } from 'rxjs/operators';

@Component({
    selector: 'contact-list',
    templateUrl: "history-administration.component.html",
    styleUrls: ['history-administration.component.scss'],
    providers: [AppService]
})
export class HistoryAdministrationComponent implements OnInit {

    @ViewChild('snav') public sidenavLeft: MatSidenav;
    @ViewChild('snav2') public sidenavRight: MatSidenav;

    lang: any = LANG;

    startDateFilter: any = '';
    endDateFilter: any = '';

    @ViewChild('appHistoryList') appHistoryList: HistoryComponent;

    subMenus: any[] = [
        {
            icon: 'fa fa-history',
            route: '/administration/history',
            label: this.lang.history,
            current: true
        }
    ];

    constructor(
        public http: HttpClient,
        public appService: AppService,
        public functions: FunctionsService) { }

    ngOnInit(): void {
        this.http.get("../../rest/history/privileges").pipe(
            tap((data: any) => {
                if (data.historyBatch) {
                    this.subMenus.push({
                        icon: 'fa fa-history',
                        route: '/administration/history-batch',
                        label: this.lang.historyBatch,
                        current: false
                    });
                }
            })
        ).subscribe();
    }
}