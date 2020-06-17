import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ValidationErrors } from '@angular/forms';
import { NotificationService } from '../../notification.service';

@Component({
    selector: 'app-database',
    templateUrl: './database.component.html',
    styleUrls: ['./database.component.scss']
})
export class DatabaseComponent implements OnInit {

    stepFormGroup: FormGroup;

    connectionState: boolean = false;

    constructor(
        private _formBuilder: FormBuilder,
        private notify: NotificationService,
    ) {
        this.stepFormGroup = this._formBuilder.group({
            dbHostCtrl: ['localhost', Validators.required],
            dbLoginCtrl: ['', Validators.required],
            dbPortCtrl: ['5432', Validators.required],
            dbPasswordCtrl: ['', Validators.required],
            dbNameCtrl: ['', Validators.required]
        });
     }

    ngOnInit(): void {

        // this.stepFormGroup.controls['firstCtrl'].setValue(this.checkStep());
        // this.stepFormGroup.controls['firstCtrl'].markAsUntouched();
    }

    isValidConnection() {
        return false;
    }

    checkConnection() {
        this.connectionState = true;
    }

    checkStep() {
        let state = 'success';
        return state;
    }

    isValidStep() {
        console.log(this.stepFormGroup.valid);

        /*Object.keys(this.stepFormGroup.controls).forEach(key => {

            const controlErrors: ValidationErrors = this.stepFormGroup.get(key).errors;
            if (controlErrors != null) {
                Object.keys(controlErrors).forEach(keyError => {
                    console.log('Key control: ' + key + ', keyError: ' + keyError + ', err value: ', controlErrors[keyError]);
                });
            }
        });*/
        return this.stepFormGroup === undefined ? false : this.stepFormGroup.valid;
    }

    getFormGroup() {
        return this.stepFormGroup;
    }

}
