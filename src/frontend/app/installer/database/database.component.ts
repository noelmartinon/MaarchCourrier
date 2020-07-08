import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ValidationErrors } from '@angular/forms';
import { NotificationService } from '../../../service/notification/notification.service';
import { HttpClient } from '@angular/common/http';
import { tap } from 'rxjs/internal/operators/tap';
import { catchError } from 'rxjs/internal/operators/catchError';
import { of } from 'rxjs/internal/observable/of';
import { LANG } from '../../translate.component';
import { StepAction } from '../types';
import { FunctionsService } from '../../../service/functions.service';
import { InstallerService } from '../installer.service';

@Component({
    selector: 'app-database',
    templateUrl: './database.component.html',
    styleUrls: ['./database.component.scss']
})
export class DatabaseComponent implements OnInit {
    lang: any = LANG;
    stepFormGroup: FormGroup;
    hide: boolean = true;

    connectionState: boolean = false;
    dbExist: boolean = false;

    dataFiles: string[] = [];

    constructor(
        public http: HttpClient,
        private _formBuilder: FormBuilder,
        private notify: NotificationService,
        private functionsService: FunctionsService,
        private installerService: InstallerService
    ) {
        this.stepFormGroup = this._formBuilder.group({
            dbHostCtrl: ['localhost', Validators.required],
            dbLoginCtrl: ['', Validators.required],
            dbPortCtrl: ['5432', Validators.required],
            dbPasswordCtrl: ['', Validators.required],
            dbNameCtrl: ['', Validators.required],
            dbSampleCtrl: ['data_fr', Validators.required],
            stateStep: ['', Validators.required]
        });
    }

    ngOnInit(): void {
        this.stepFormGroup.controls['dbHostCtrl'].valueChanges.pipe(
            tap(() => this.stepFormGroup.controls['stateStep'].setValue(''))
        ).subscribe();
        this.stepFormGroup.controls['dbLoginCtrl'].valueChanges.pipe(
            tap(() => this.stepFormGroup.controls['stateStep'].setValue(''))
        ).subscribe();
        this.stepFormGroup.controls['dbPortCtrl'].valueChanges.pipe(
            tap(() => this.stepFormGroup.controls['stateStep'].setValue(''))
        ).subscribe();
        this.stepFormGroup.controls['dbPasswordCtrl'].valueChanges.pipe(
            tap(() => this.stepFormGroup.controls['stateStep'].setValue(''))
        ).subscribe();
        this.stepFormGroup.controls['dbNameCtrl'].valueChanges.pipe(
            tap(() => this.stepFormGroup.controls['stateStep'].setValue(''))
        ).subscribe();

        this.getDataFiles();
    }

    getDataFiles() {
        this.http.get(`../rest/installer/sqlDataFiles`).pipe(
            tap((data: any) => {
                this.dataFiles = data.dataFiles;
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    isValidConnection() {
        return false;
    }

    initStep() {
        if (this.installerService.isStepAlreadyLaunched('database')) {
            this.stepFormGroup.disable();
        }
    }

    checkConnection() {

        const info = {
            server: this.stepFormGroup.controls['dbHostCtrl'].value,
            port: this.stepFormGroup.controls['dbPortCtrl'].value,
            user: this.stepFormGroup.controls['dbLoginCtrl'].value,
            password: this.stepFormGroup.controls['dbPasswordCtrl'].value,
            name: this.stepFormGroup.controls['dbNameCtrl'].value
        };

        this.http.get(`../rest/installer/databaseConnection`, { observe: 'response', params: info }).pipe(
            tap((data: any) => {
                this.dbExist = data.status === 200;
                this.notify.success(this.lang.rightInformations);
                this.stepFormGroup.controls['stateStep'].setValue('success');
            }),
            catchError((err: any) => {
                this.dbExist = false;
                if (err.error.errors === 'Given database has tables') {
                    this.notify.error(this.lang.dbNotEmpty);
                } else {
                    this.notify.error(this.lang.badInformations);
                }
                this.stepFormGroup.markAllAsTouched();
                this.stepFormGroup.controls['stateStep'].setValue('');
                return of(false);
            })
        ).subscribe();
    }

    checkStep() {
        return this.stepFormGroup.valid;
    }

    isValidStep() {
        if (this.installerService.isStepAlreadyLaunched('database')) {
            return true;
        } else {
            return this.stepFormGroup === undefined ? false : this.stepFormGroup.valid;
        }
    }

    isEmptyConnInfo() {
        return this.stepFormGroup.controls['dbHostCtrl'].invalid ||
            this.stepFormGroup.controls['dbPortCtrl'].invalid ||
            this.stepFormGroup.controls['dbLoginCtrl'].invalid ||
            this.stepFormGroup.controls['dbPasswordCtrl'].invalid ||
            this.stepFormGroup.controls['dbNameCtrl'].invalid;
    }

    getFormGroup() {
        return this.installerService.isStepAlreadyLaunched('database') ? true : this.stepFormGroup;
    }

    getInfoToInstall(): StepAction[] {
        return [{
            idStep : 'database',
            body: {
                server: this.stepFormGroup.controls['dbHostCtrl'].value,
                port: this.stepFormGroup.controls['dbPortCtrl'].value,
                user: this.stepFormGroup.controls['dbLoginCtrl'].value,
                password: this.stepFormGroup.controls['dbPasswordCtrl'].value,
                name: this.stepFormGroup.controls['dbNameCtrl'].value,
                data: this.stepFormGroup.controls['dbSampleCtrl'].value
            },
            route : {
                method : 'POST',
                url : '../rest/installer/database'
            },
            description: this.lang.stepDatabaseActionDesc,
            installPriority: 2
        }];
    }

}
