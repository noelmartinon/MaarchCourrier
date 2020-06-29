import { Component, OnInit, EventEmitter, Output } from '@angular/core';
import { FormGroup, FormBuilder, Validators, ValidatorFn } from '@angular/forms';
import { NotificationService } from '../../../service/notification/notification.service';
import { LANG } from '../../translate.component';
import { tap } from 'rxjs/internal/operators/tap';
import { InstallerService } from '../installer.service';

@Component({
    selector: 'app-useradmin',
    templateUrl: './useradmin.component.html',
    styleUrls: ['./useradmin.component.scss']
})
export class UseradminComponent implements OnInit {
    lang: any = LANG;
    stepFormGroup: FormGroup;

    hide: boolean = true;

    @Output() tiggerInstall = new EventEmitter<string>();

    constructor(
        private _formBuilder: FormBuilder,
        private notify: NotificationService,
        private installerService: InstallerService
    ) {

        const valLogin: ValidatorFn[] = [Validators.pattern(/^[\w.@-]*$/), Validators.required];

        this.stepFormGroup = this._formBuilder.group({
            login: ['superadmin', valLogin],
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

    initStep() {
        if (this.installerService.isStepAlreadyLaunched('userAdmin')) {
            this.stepFormGroup.disable();
        }
    }

    isValidStep() {
        return this.stepFormGroup === undefined ? false : this.stepFormGroup.valid || this.installerService.isStepAlreadyLaunched('userAdmin');
    }

    getFormGroup() {
        return this.stepFormGroup;
    }

    getInfoToInstall(): any[] {
        return [];
        /*return {
            idStep : 'userAdmin',
            body : {
                login: this.stepFormGroup.controls['login'].value,
                password: this.stepFormGroup.controls['password'].value,
                email: this.stepFormGroup.controls['email'].value,
            },
            route : '/installer/useradmin',
            description: this.lang.stepUserAdminActionDesc,
            installPriority: 3
        };*/
    }

    launchInstall() {
        this.tiggerInstall.emit();
    }
}
