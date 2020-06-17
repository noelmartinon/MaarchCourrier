import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { NotificationService } from '../../notification.service';

@Component({
    selector: 'app-docservers',
    templateUrl: './docservers.component.html',
    styleUrls: ['./docservers.component.scss']
})
export class DocserversComponent implements OnInit {

    stepFormGroup: FormGroup;

    docserversPath: string = '/opt/maaarch/docservers/';

    constructor(
        private _formBuilder: FormBuilder,
        private notify: NotificationService,
    ) {
        this.stepFormGroup = this._formBuilder.group({
            firstCtrl: ['', Validators.required],
        });
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
        this.stepFormGroup.controls['firstCtrl'].setValue('success');
        this.notify.success('Le chemin est disponible');
    }

}
