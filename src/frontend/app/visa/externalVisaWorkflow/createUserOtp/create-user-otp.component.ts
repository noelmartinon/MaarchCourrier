import { Component, Input, OnInit, Output, EventEmitter } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import { CdkDragDrop, moveItemInArray } from '@angular/cdk/drag-drop';
import { FunctionsService } from '@service/functions.service';
import { tap, catchError } from 'rxjs/operators';
import { FormControl } from '@angular/forms';
import { ScanPipe } from 'ngx-pipes';
import { Observable, of } from 'rxjs';
import { MatDialogRef } from '@angular/material/dialog';

@Component({
    templateUrl: 'create-user-otp.component.html',
    styleUrls: ['create-user-otp.component.scss'],
})

export class CreateUserOtpComponent implements OnInit {

    // For TEST

    sources: any[] = [
        {
            id: 1,
            label: 'Yousign 1',
            type: 'yousign'
        },
        {
            id: 2,
            label: 'Yousign 2',
            type: 'yousign'
        }
    ];

    sampleRoles: any[] = [
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
        sourceId: this.sources[0].id,
        type: this.sources[0].type
    };

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        public functions: FunctionsService,
        private dialogRef: MatDialogRef<CreateUserOtpComponent>,
        public notify: NotificationService

    ) { }

    async ngOnInit(): Promise<void> {
        // await this.getConfig();
    }

    getConfig() {
        return new Promise((resolve) => {
            this.http.get('../rest/maarchParapheurOtp').pipe(
                tap((data: any) => {
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

    addOtpUser() {
        this.dialogRef.close({otp: this.userOTP});
    }

    isValidForm() {
        return Object.values(this.userOTP).every(item => (item !== '')) && this.validFormat();
    }

    validFormat() {
        const phoneRegex = /^(\+33)[1-9]{9}$/;
        const emailReegex = /[a-zA-Z0-9._%+-]+@[a-zA-Z0-9._-]+\.[a-zA-Z]{2,4}$/;
        return (this.userOTP.phone.length > 1 && this.userOTP.phone.trim().match(phoneRegex) !== null) && (this.userOTP.email.length > 1 && this.userOTP.email.trim().match(emailReegex) !== null);
    }
}
