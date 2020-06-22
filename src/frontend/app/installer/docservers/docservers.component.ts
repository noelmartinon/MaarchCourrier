import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { NotificationService } from '../../../service/notification/notification.service';
import { tap } from 'rxjs/internal/operators/tap';
import { LANG } from '../../translate.component';

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
