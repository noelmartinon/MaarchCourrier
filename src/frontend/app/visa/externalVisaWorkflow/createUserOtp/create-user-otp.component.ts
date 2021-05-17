import { Component, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import { FunctionsService } from '@service/functions.service';
import { tap, catchError } from 'rxjs/operators';
import { MatDialogRef } from '@angular/material/dialog';
import { of } from 'rxjs';

@Component({
    templateUrl: 'create-user-otp.component.html',
    styleUrls: ['create-user-otp.component.scss'],
})

export class CreateUserOtpComponent implements OnInit {

    sources: any[] = [];

    currentSource: any[] = [];

    roles: any[] = [
        {
            id: 'otp_visa_yousign',
            label: this.translate.instant('lang.otp_visa_yousign')
        },
        {
            id: 'otp_sign_yousign',
            label: this.translate.instant('lang.otp_sign_yousign')
        }
    ];

    securityModes: any[] = [
        {
            id: 'sms',
            label: this.translate.instant('lang.sms')
        },
        {
            id: 'email',
            label: this.translate.instant('lang.email')
        }
    ];

    userOTP: any = {
        firstname: '',
        lastname: '',
        email: '',
        phone: '',
        role: '',
        security: '',
        sourceId: '',
        type: ''
    };

    loading: boolean = true;

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        public functions: FunctionsService,
        private dialogRef: MatDialogRef<CreateUserOtpComponent>,
        public notify: NotificationService

    ) { }

    async ngOnInit(): Promise<void> {
        await this.getConfig();
    }

    getConfig() {
        return new Promise((resolve) => {
            this.http.get('../rest/maarchParapheurOtp').pipe(
                tap((data: any) => {
                    if (data) {
                        this.sources = data.otp;
                        this.userOTP.sourceId = this.sources[0].id;
                        this.userOTP.type = this.sources[0].type;
                        this.setCurrentSource(this.sources[0].id);
                    }
                    this.loading = false;
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    addOtpUser() {
        this.dialogRef.close({otp: this.userOTP});
    }

    isValidForm() {
        return Object.values(this.userOTP).every(item => (item !== '')) && this.validFormat();
    }

    validFormat() {
        const phoneRegex = /^((\+)33)[1-9](\d{2}){4}$/;
        const emailReegex = /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/;
        return (this.userOTP.phone.length > 1 && this.userOTP.phone.trim().match(phoneRegex) !== null) && (this.userOTP.email.length > 1 && this.userOTP.email.trim().match(emailReegex) !== null);
    }

    setCurrentSource(id: any) {
        const selectedSource: any = this.sources.filter((item: any) => item.id === id)[0];
        this.userOTP.type = selectedSource.type;
        this.currentSource = selectedSource.securityModes;
        this.userOTP.security = this.currentSource[0];
    }
}
