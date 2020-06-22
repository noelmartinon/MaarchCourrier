import { Component, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router, ActivatedRoute } from '@angular/router';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../../service/notification/notification.service';
import { HeaderService } from '../../../service/header.service';
import { FormControl, Validators } from '@angular/forms';
import { AppService } from '../../../service/app.service';

@Component({
    templateUrl: 'status-administration.component.html'
})
export class StatusAdministrationComponent implements OnInit {

    lang: any = LANG;

    creationMode: boolean;
    statusIdAvailable: boolean;

    statusIdentifier: string;
    status: any = {
        id: null,
        label_status: null,
        can_be_searched: null,
        can_be_modified: null,
        img_filename: 'fm-letter'
    };
    statusImages: any = '';

    loading: boolean = false;

    statusId = new FormControl('', [Validators.required, Validators.pattern(/^[\w.-]*$/)]);

    getErrorMessage() {
        return this.statusId.hasError('required') ? this.lang.enterValue :
            this.statusId.hasError('pattern') ? this.lang.patternId : '';
    }

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

        this.route.params.subscribe((params: any) => {
            if (typeof params['identifier'] === 'undefined') {
                this.headerService.setHeader(this.lang.statusCreation);

                this.http.get('../rest/administration/statuses/new')
                    .subscribe((data: any) => {
                        this.status.img_filename = 'fm-letter';
                        this.status.can_be_searched = true;
                        this.status.can_be_modified = true;
                        this.statusImages = data['statusImages'];
                        this.creationMode = true;
                        this.loading = false;
                    });
                this.statusIdAvailable = false;
            } else {

                this.creationMode = false;
                this.statusIdentifier = params['identifier'];
                this.http.get('../rest/statuses/' + params['identifier'])
                    .subscribe((data: any) => {
                        this.status = data['status'][0];
                        this.headerService.setHeader(this.lang.statusModification, this.status['label_status']);

                        if (this.status.can_be_searched === 'Y') {
                            this.status.can_be_searched = true;
                        } else {
                            this.status.can_be_searched = false;
                        }
                        if (this.status.can_be_modified === 'Y') {
                            this.status.can_be_modified = true;
                        } else {
                            this.status.can_be_modified = false;
                        }
                        this.statusImages = data['statusImages'];
                        this.statusIdAvailable = true;
                        this.loading = false;
                    }, (err: any) => {
                        this.notify.error(err.error.errors);
                    });
            }
        });
    }

    isAvailable() {
        if (this.status.id) {
            this.http.get('../rest/status/' + this.status.id)
                .subscribe(() => {
                    this.statusIdAvailable = false;
                }, (err) => {
                    this.statusIdAvailable = false;
                    if (err.error.errors === 'id not found') {
                        this.statusIdAvailable = true;
                    }
                });
        } else {
            this.statusIdAvailable = false;
        }
    }

    submitStatus() {
        if (this.creationMode === true) {
            this.http.post('../rest/statuses', this.status)
                .subscribe(() => {
                    this.notify.success(this.lang.statusAdded);
                    this.router.navigate(['administration/statuses']);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        } else if (this.creationMode === false) {

            this.http.put('../rest/statuses/' + this.statusIdentifier, this.status)
                .subscribe(() => {
                    this.notify.success(this.lang.statusUpdated);
                    this.router.navigate(['administration/statuses']);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }
}
