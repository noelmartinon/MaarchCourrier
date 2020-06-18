import { Component, OnInit } from '@angular/core';
import { FormGroup, FormBuilder, Validators } from '@angular/forms';
import { NotificationService } from '../../notification.service';
import { LANG } from '../../translate.component';
import { tap } from 'rxjs/internal/operators/tap';

@Component({
    selector: 'app-useradmin',
    templateUrl: './useradmin.component.html',
    styleUrls: ['./useradmin.component.scss']
})
export class UseradminComponent implements OnInit {
    lang: any = LANG;
    stepFormGroup: FormGroup;

    hide: boolean = true;

    constructor(
        private _formBuilder: FormBuilder,
        private notify: NotificationService,
    ) {
        this.stepFormGroup = this._formBuilder.group({
            login: [{ value: 'superadmin', disabled: true }, Validators.required],
            password: ['', Validators.required],
            passwordConfirm: ['', Validators.required],
            email: ['dev@maarch.org', Validators.required],
        });


    }

    ngOnInit(): void {
        this.stepFormGroup.controls['password'].valueChanges.pipe(
            tap((data) => {
                if (data !== this.stepFormGroup.controls['passwordConfirm'].value) {
                    this.stepFormGroup.controls['password'].setErrors({ 'incorrect': true });
                    this.stepFormGroup.controls['passwordConfirm'].setErrors({ 'incorrect': true });
                    this.stepFormGroup.controls['passwordConfirm'].markAsTouched();
                } else {
                    this.stepFormGroup.controls['password'].setErrors(null);
                    this.stepFormGroup.controls['passwordConfirm'].setErrors(null);
                }
            })
        ).subscribe();
        this.stepFormGroup.controls['passwordConfirm'].valueChanges.pipe(
            tap((data) => {
                if (data !== this.stepFormGroup.controls['password'].value) {
                    this.stepFormGroup.controls['password'].setErrors({ 'incorrect': true });
                    this.stepFormGroup.controls['password'].markAsTouched();
                    this.stepFormGroup.controls['passwordConfirm'].setErrors({ 'incorrect': true });
                } else {
                    this.stepFormGroup.controls['password'].setErrors(null);
                    this.stepFormGroup.controls['passwordConfirm'].setErrors(null);
                }
            })
        ).subscribe();
    }

    isValidStep() {
        return this.stepFormGroup === undefined ? false : this.stepFormGroup.valid;
    }

    getFormGroup() {
        return this.stepFormGroup;
    }

    launchInstall() {

    }
}
