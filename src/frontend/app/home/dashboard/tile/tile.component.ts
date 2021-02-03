import { Component, OnInit, AfterViewInit, Input } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { AppService } from '@service/app.service';
import { DashboardService } from '@appRoot/home/dashboard/dashboard.service';
import { catchError, tap } from 'rxjs/operators';
import { of } from 'rxjs';
import { NotificationService } from '@service/notification/notification.service';

@Component({
    selector: 'app-tile',
    templateUrl: 'tile.component.html',
    styleUrls: ['tile.component.scss']
})
export class TileDashboardComponent implements OnInit, AfterViewInit {

    @Input() view: string = 'list';

    @Input() tile: any = null;

    loading: boolean = true;

    resources: any[] = [];
    countResources: number = 0;
    route: string = null;
    viewDocRoute: string = null;

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        private notify: NotificationService,
        public appService: AppService,
        public dashboardService: DashboardService,
    ) { }

    ngOnInit(): void { }

    async ngAfterViewInit(): Promise<void> {
        await this['get_' + this.view]();
        this.route = this.tile.views.find((viewItem: any) => viewItem.id === this.view).route;
        this.viewDocRoute = this.tile.views.find((viewItem: any) => viewItem.id === this.view).viewDocRoute;
        this.loading = false;
    }

    async changeView(view: string, extraParams: any) {
        this.view = null;
        this.loading = true;
        await this['get_' + view](extraParams);
        this.view = view;
        this.route = this.tile.views.find((viewItem: any) => viewItem.id === this.view).route;
        this.loading = false;
    }

    async get_list(extraParams: any) {
        return new Promise((resolve) => {
            this.http.get(`../rest/tiles/${this.tile.id}`).pipe(
                tap((data: any) => {
                    const resources = data.tile.resources.map((resource: any) => {
                        let contactLabel = '';
                        let contactTitle = '';
                        if (resource.senders.length > 0) {
                            if (resource.senders.length === 1) {
                                contactLabel = resource.senders[0];
                                contactTitle = this.translate.instant('lang.sender') + ': ' + resource.senders[0];
                            } else {
                                contactLabel = resource.senders.length + ' ' + this.translate.instant('lang.senders');
                                contactTitle = resource.senders;
                            }
                        } else if (resource.recipients.length > 0) {
                            if (resource.recipients.length === 1) {
                                contactLabel = resource.recipients[0];
                                contactTitle = this.translate.instant('lang.sender') + ': ' + resource.recipients[0];
                            } else {
                                contactLabel = resource.recipients.length + ' ' + this.translate.instant('lang.recipients');
                                contactTitle = resource.recipients;
                            }
                        }
                        delete resource.recipients;
                        delete resource.senders;
                        return {
                            ...resource,
                            contactLabel: contactLabel,
                            contactTitle: contactTitle
                        };
                    });
                    this.resources = resources
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    async get_resume(extraParams: any) {
        return new Promise((resolve) => {
            this.http.get(`../rest/tiles/${this.tile.id}`).pipe(
                tap((data: any) => {
                    this.countResources = data.tile.resourcesNumber;
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    async get_chart(extraParams: any) {
        return new Promise((resolve) => {
            this.http.get(`../rest/tiles/${this.tile.id}`).pipe(
                tap((data: any) => {
                    this.resources = data.tile.resources;
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }
}
