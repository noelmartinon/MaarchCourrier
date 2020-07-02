import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { NotificationService } from '../../../service/notification/notification.service';
import { tap } from 'rxjs/internal/operators/tap';
import { LANG } from '../../translate.component';
import { HttpClient } from '@angular/common/http';
import { catchError } from 'rxjs/internal/operators/catchError';
import { of } from 'rxjs/internal/observable/of';
import { InstallerService } from '../installer.service';

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
        private installerService: InstallerService
    ) {
        this.stepFormGroup = this._formBuilder.group({
            docserversPath: ['/opt/maarch/docservers/', Validators.required],
            stateStep: ['', Validators.required],
        });

        this.stepFormGroup.controls['docserversPath'].valueChanges.pipe(
            tap(() => this.stepFormGroup.controls['stateStep'].setValue(''))
        ).subscribe();
    }

    ngOnInit(): void {
    }


    isValidStep() {
        return this.stepFormGroup === undefined ? false : this.stepFormGroup.valid || this.installerService.isStepAlreadyLaunched('docserver');
    }

    initStep() {
        if (this.installerService.isStepAlreadyLaunched('docserver')) {
            this.stepFormGroup.disable();
        }
    }

    getFormGroup() {
        return this.stepFormGroup;
    }

    checkAvailability() {
        const info = {
            path: this.stepFormGroup.controls['docserversPath'].value,
        };

        this.http.get(`../rest/installer/docservers`, { params: info }).pipe(
            tap((data: any) => {
                this.notify.success(this.lang.rightInformations);
                this.stepFormGroup.controls['stateStep'].setValue('success');
            }),
            catchError((err: any) => {
                this.notify.error(this.lang.pathUnreacheable);
                this.stepFormGroup.controls['stateStep'].setValue('');
                return of(false);
            })
        ).subscribe();
    }

    getInfoToInstall(): any[] {
        return [{
            idStep : 'docserver',
            body: {
                path: this.stepFormGroup.controls['docserversPath'].value,
            },
            route : {
                method : 'POST',
                url : '../rest/installer/docservers'
            },
            description: this.lang.stepDocserversActionDesc,
            installPriority: 3
        }];
    }

}
