import { Component, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import {FormBuilder, FormGroup, ValidatorFn, Validators} from '@angular/forms';
import {catchError, tap} from 'rxjs/operators';
import { of } from 'rxjs/internal/observable/of';
import { NotificationService } from '../../../service/notification/notification.service';
import { HeaderService } from '../../../service/header.service';
import { FunctionsService } from '../../../service/functions.service';
import {AppService} from '../../../service/app.service';
import {TranslateService} from '@ngx-translate/core';
import {ActivatedRoute} from '@angular/router';

@Component({
    selector: 'app-acknowledgement-reception',
    templateUrl: 'acknowledgement-reception.component.html'
})

export class AcknowledgementReceptionComponent implements OnInit {

    loading: boolean = false;

    type: any;
    number: any;
    receivedDate: any;
    reason: any;
    reasonOther: any;

    today: Date = new Date();

    adminFormGroup: FormGroup;

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        private headerService: HeaderService,
        public functions: FunctionsService,
        public appService: AppService,
        public translate: TranslateService,
        private _formBuilder: FormBuilder,
        private route: ActivatedRoute
    ) {

    }

    ngOnInit() {
        this.route.params.subscribe(async () => {
            this.headerService.setHeader(this.translate.instant('lang.arReception'));
            const validatorNumber: ValidatorFn[] = [Validators.pattern(/(2C|2D|RW) ([0-9]{3} [0-9]{3} [0-9]{4}) ([0-9])/), Validators.required];
            this.adminFormGroup = this._formBuilder.group({
                type:              ['', Validators.required],
                number:            ['', validatorNumber],
                receivedDate:      ['', Validators.required],
                returnReason:      ['', Validators.required],
                returnReasonOther: ['', Validators.required]
            });
            this.loading = false;
        });
    }

    receiveAcknowledgement() {
        const data = {
            type: this.type,
            number: this.number,
            receivedDate: this.receivedDate,
            returnReason: this.reason,
            returnReasonOther: this.reasonOther
        };

        if (this.type === 'distributed') {
            if (!this.adminFormGroup.get('number').valid) {
                this.notify.error(this.translate.instant('lang.fieldsNotValid'));
                return;
            }
        } else {
            if (!this.adminFormGroup.get('number').valid || !this.adminFormGroup.get('receivedDate').valid || !this.adminFormGroup.get('returnReason').valid) {
                this.notify.error(this.translate.instant('lang.fieldsNotValid'));
                return;
            }
        }

        this.http.put('../rest/registeredMails/acknowledgement', data).pipe(
            tap(() => {
                this.type = '';
                this.number = '';
                this.receivedDate = '';
                this.reason = '';
                this.reasonOther = '';
            }),
            catchError((err) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }
}
