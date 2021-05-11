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
    sampleRoles: any[] = [
        {
            id: 'visa',
            label: this.translate.instant('lang.visa')
        },
        {
            id: 'stamp',
            label: this.translate.instant('lang.stamp')
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
        type: 'yousign',
        firstname: '',
        lastname: '',
        email: '',
        phone: '',
        role: '',
        mode: ''
    };

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        public functions: FunctionsService,
        private dialogRef: MatDialogRef<CreateUserOtpComponent>

    ) { }

    ngOnInit(): void {}

    addOtpUser() {
        this.dialogRef.close({otp: this.userOTP});
    }

    isValidForm() {
        return Object.values(this.userOTP).every(item => (item !== '')) && this.validFormat();
    }

    validFormat() {
        const phoneRegex = /^((\+)33)[1-9](\d{2}){4}$/g;
        const emailReegex = /\S+@\S+\.\S+/;
        return (this.userOTP.phone.length > 1 && this.userOTP.phone.trim().match(phoneRegex) !== null) && (this.userOTP.email.length > 1 && this.userOTP.email.trim().match(emailReegex) !== null);
    }
}
