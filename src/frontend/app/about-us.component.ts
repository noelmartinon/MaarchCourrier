import { Component, OnInit } from '@angular/core';
import { LANG } from './translate.component';
import { HeaderService } from '../service/header.service';
import { AppService } from '../service/app.service';
import { environment } from '../environments/environment';
import {catchError, tap} from 'rxjs/operators';
import {HttpClient} from '@angular/common/http';
import {of} from 'rxjs/internal/observable/of';
import {NotificationService} from '../service/notification/notification.service';

@Component({
    templateUrl: 'about-us.component.html',
    styleUrls: ['about-us.component.css']
})
export class AboutUsComponent implements OnInit {

    applicationVersion: string;
    lang: any = LANG;

    loading: boolean = false;

    commitHash: string = this.lang.undefined;

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        private headerService: HeaderService,
        public appService: AppService) { }

    async ngOnInit() {
        this.headerService.setHeader(this.lang.aboutUs);

        this.applicationVersion = environment.VERSION;
        this.loading = false;

        await this.loadCommitInformation();
    }

    loadCommitInformation() {
        return new Promise((resolve) => {
            this.http.get('../rest/commitInformation').pipe(
                tap((data: any) => {
                    this.commitHash = data.hash !== null ? data.hash : this.lang.undefined;
                    console.log(data);
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
