import { Component, Inject, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import { FunctionsService } from '@service/functions.service';
import { tap, catchError } from 'rxjs/operators';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { of } from 'rxjs';

@Component({
    templateUrl: 'create-user-otp.component.html',
    styleUrls: ['create-user-otp.component.scss'],
})

export class CreateUserOtpComponent implements OnInit {

    sources: any[] = [];

    currentSource: any[] = [];

    availableRoles: any[] = [
        {
            id: 'visa',
            label: this.translate.instant('lang.otp_visa_yousign')
        },
        {
            id: 'sign',
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
        security: '',
        sourceId: '',
        type: '',
        role: 'sign',
        availableRoles: this.availableRoles.map((role: any) => role.id)
    };

    loading: boolean = true;

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        public functions: FunctionsService,
        private dialogRef: MatDialogRef<CreateUserOtpComponent>,
        public notify: NotificationService,
        @Inject(MAT_DIALOG_DATA) public data: any

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
                        this.setCurrentSource(this.sources[0].id);
                        if (this.data === null) {
                            this.userOTP.sourceId = this.sources[0].id;
                            this.userOTP.type = this.sources[0].type;
                        } else {
                            this.userOTP = this.data;
                        }
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
        return (!this.functions.empty(this.userOTP.phone) && this.userOTP.phone.trim().match(phoneRegex) !== null) && (!this.functions.empty(this.userOTP.email) && this.userOTP.email.trim().match(emailReegex) !== null);
    }

    setCurrentSource(id: any) {
        const selectedSource: any = this.sources.filter((item: any) => item.id === id)[0];
        this.userOTP.type = selectedSource.type;
        this.currentSource = selectedSource.securityModes;
        this.userOTP.security = this.currentSource[0];
    }
}
