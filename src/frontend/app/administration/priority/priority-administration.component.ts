import { Component, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router, ActivatedRoute } from '@angular/router';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../../service/notification/notification.service';
import { HeaderService } from '../../../service/header.service';
import { AppService } from '../../../service/app.service';

@Component({
    templateUrl: 'priority-administration.component.html'
})
export class PriorityAdministrationComponent implements OnInit {

    id: string;
    creationMode: boolean;
    lang: any = LANG;
    loading: boolean = false;

    priority: any = {
        color: '#135f7f',
        delays: '0',
    };

    constructor(
        public http: HttpClient,
        private route: ActivatedRoute,
        private router: Router,
        private notify: NotificationService,
        private headerService: HeaderService,
        public appService: AppService
    ) { }

    ngOnInit(): void {
        this.loading = true;

        this.route.params.subscribe((params) => {

            if (typeof params['id'] === 'undefined') {
                this.headerService.setHeader(this.lang.priorityCreation);

                this.creationMode = true;
                this.loading = false;
            } else {

                this.creationMode = false;
                this.id = params['id'];
                this.http.get('../rest/priorities/' + this.id)
                    .subscribe((data: any) => {
                        this.priority = data.priority;
                        this.headerService.setHeader(this.lang.priorityModification, this.priority.label);
                        this.loading = false;
                    }, (err) => {
                        this.notify.handleErrors(err);
                    });
            }
        });
    }

    onSubmit() {
        if (this.creationMode) {
            this.http.post('../rest/priorities', this.priority)
                .subscribe(() => {
                    this.notify.success(this.lang.priorityAdded);
                    this.router.navigate(['/administration/priorities']);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        } else {
            this.http.put('../rest/priorities/' + this.id, this.priority)
                .subscribe(() => {
                    this.notify.success(this.lang.priorityUpdated);
                    this.router.navigate(['/administration/priorities']);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }
}
