import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { NotificationService } from '../../../service/notification/notification.service';
import { tap } from 'rxjs/internal/operators/tap';
import { LANG } from '../../translate.component';
import { catchError } from 'rxjs/operators';
import { of } from 'rxjs';
import { HttpClient } from '@angular/common/http';

@Component({
    selector: 'app-docservers',
    templateUrl: './docservers.component.html',
    styleUrls: ['./docservers.component.scss']
})
export class DocserversComponent implements OnInit {
    lang: any = LANG;
    stepFormGroup: FormGroup;

    constructor(
        private _formBuilder: FormBuilder,
        private notify: NotificationService,
        public http: HttpClient,
    ) {
        this.stepFormGroup = this._formBuilder.group({
            docserversPath: ['/opt/maaarch/docservers/', Validators.required],
            stateStep: ['', Validators.required],
        });

        this.stepFormGroup.controls['docserversPath'].valueChanges.pipe(
            tap(() => this.stepFormGroup.controls['stateStep'].setValue(''))
        ).subscribe();
    }

    ngOnInit(): void {
    }


    isValidStep() {
        return this.stepFormGroup === undefined ? false : this.stepFormGroup.valid;
    }


    getFormGroup() {
        return this.stepFormGroup;
    }

    checkAvailability() {
        const info = {
            path: this.stepFormGroup.controls['docserversPath'].value,
        };

        /*this.http.get(`../rest/installer/docservers`, { params: info }).pipe(
            tap((data: any) => {
                this.notify.success(this.lang.rightInformations);
                this.stepFormGroup.controls['stateStep'].setValue('success');
            }),
            catchError((err: any) => {
                this.notify.error(this.lang.badInformations);
                this.stepFormGroup.controls['stateStep'].setValue('');
                return of(false);
            })
        ).subscribe();*/

        // FOR TEST
        this.stepFormGroup.controls['stateStep'].setValue('success');
        this.notify.success(this.lang.rightInformations);
    }

    getInfoToInstall(): any[] {
        return [];
        /*return {
            body : {
                appName: this.stepFormGroup.controls['docserversPath'].value,
            },
            route : '/installer/docservers'
        };*/
    }

}
