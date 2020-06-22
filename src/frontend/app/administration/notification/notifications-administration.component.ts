import { Component, ViewChild, OnInit, TemplateRef, ViewContainerRef } from '@angular/core';
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
    templateUrl: 'notifications-administration.component.html'
})
export class NotificationsAdministrationComponent implements OnInit {

    @ViewChild('snav2', { static: true }) public sidenavRight: MatSidenav;
    @ViewChild('adminMenuTemplate', { static: true }) adminMenuTemplate: TemplateRef<any>;

    notifications: any[] = [];
    loading: boolean = false;
    lang: any = LANG;

    hours: any;
    minutes: any;

    months: any = [];

    dom: any = [];

    dow: any = [];

    newCron: any = {
        'm': '',
        'h': '',
        'dom': '',
        'mon': '',
        'cmd': '',
        'state': 'normal'
    };

    authorizedNotification: any;
    crontab: any;

    displayedColumns = ['notification_id', 'description', 'is_enabled', 'notifications'];
    dataSource = new MatTableDataSource(this.notifications);
    @ViewChild(MatPaginator, { static: false }) paginator: MatPaginator;
    @ViewChild(MatSort, { static: false }) sort: MatSort;
    applyFilter(filterValue: string) {
        filterValue = filterValue.trim(); // Remove whitespace
        filterValue = filterValue.toLowerCase(); // MatTableDataSource defaults to lowercase matches
        this.dataSource.filter = filterValue;
        this.dataSource.filterPredicate = (template, filter: string) => {
            return this.functions.filterUnSensitive(template, filter, ['notification_id', 'description']);
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
        this.headerService.setHeader(this.lang.administration + ' ' + this.lang.notifications);

        this.headerService.injectInSideBarLeft(this.adminMenuTemplate, this.viewContainerRef, 'adminMenu');

        this.loading = true;

        this.http.get('../rest/notifications')
            .subscribe((data: any) => {
                this.notifications = data.notifications;
                this.loading = false;
                setTimeout(() => {
                    this.dataSource = new MatTableDataSource(this.notifications);
                    this.dataSource.paginator = this.paginator;
                    this.dataSource.sortingDataAccessor = this.functions.listSortingDataAccessor;
                    this.sort.active = 'notification_id';
                    this.sort.direction = 'asc';
                    this.dataSource.sort = this.sort;
                }, 0);
            }, (err: any) => {
                this.notify.error(err.error.errors);
            });
    }

    deleteNotification(notification: any) {
        const r = confirm(this.lang.deleteMsg);

        if (r) {
            this.http.delete('../rest/notifications/' + notification.notification_sid)
                .subscribe((data: any) => {
                    this.notifications = data.notifications;
                    setTimeout(() => {
                        this.dataSource = new MatTableDataSource(this.notifications);
                        this.dataSource.paginator = this.paginator;
                        this.dataSource.sort = this.sort;
                    }, 0);
                    this.sidenavRight.close();
                    this.notify.success(this.lang.notificationDeleted);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

    loadCron() {

        this.hours = [{ label: this.lang.eachHour, value: '*' }];
        this.minutes = [{ label: this.lang.eachMinute, value: '*' }];

        this.months = [
            { label: this.lang.eachMonth, value: '*' },
            { label: this.lang.january, value: '1' },
            { label: this.lang.february, value: '2' },
            { label: this.lang.march, value: '3' },
            { label: this.lang.april, value: '4' },
            { label: this.lang.may, value: '5' },
            { label: this.lang.june, value: '6' },
            { label: this.lang.july, value: '7' },
            { label: this.lang.august, value: '8' },
            { label: this.lang.september, value: '9' },
            { label: this.lang.october, value: '10' },
            { label: this.lang.november, value: '11' },
            { label: this.lang.december, value: '12' }
        ];

        this.dom = [{ label: this.lang.notUsed, value: '*' }];

        this.dow = [
            { label: this.lang.eachDay, value: '*' },
            { label: this.lang.monday, value: '1' },
            { label: this.lang.tuesday, value: '2' },
            { label: this.lang.wednesday, value: '3' },
            { label: this.lang.thursday, value: '4' },
            { label: this.lang.friday, value: '5' },
            { label: this.lang.saturday, value: '6' },
            { label: this.lang.sunday, value: '7' }
        ];

        this.newCron = {
            'm': '',
            'h': '',
            'dom': '',
            'mon': '',
            'cmd': '',
            'state': 'normal'
        };

        for (let it = 0; it <= 23; it++) {
            this.hours.push({ label: it, value: String(it) });
        }

        for (let it = 0; it <= 59; it++) {
            this.minutes.push({ label: it, value: String(it) });
        }

        for (let it = 1; it <= 31; it++) {
            this.dom.push({ label: it, value: String(it) });
        }

        this.http.get('../rest/notifications/schedule')
            .subscribe((data: any) => {
                this.crontab = data.crontab;
                this.authorizedNotification = data.authorizedNotification;
            }, (err) => {
                this.notify.error(err.error.errors);
            });

    }

    saveCron() {
        const description = this.newCron.cmd.split('/');
        this.newCron.description = description[description.length - 1];
        this.crontab.push(this.newCron);
        this.http.post('../rest/notifications/schedule', this.crontab)
            .subscribe((data: any) => {
                this.newCron = {
                    'm': '',
                    'h': '',
                    'dom': '',
                    'mon': '',
                    'cmd': '',
                    'description': '',
                    'state': 'normal'
                };
                this.notify.success(this.lang.notificationScheduleUpdated);
            }, (err) => {
                this.crontab.pop();
                this.notify.error(err.error.errors);
            });
    }

    deleteCron(i: number) {
        this.crontab[i].state = 'deleted';
        this.http.post('../rest/notifications/schedule', this.crontab)
            .subscribe((data: any) => {
                this.crontab.splice(i, 1);
                this.notify.success(this.lang.notificationScheduleUpdated);
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }
}
