import { Component, OnInit, Inject, AfterViewInit } from '@angular/core';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { LANG } from '../../../app/translate.component';
import { HttpClient } from '@angular/common/http';
import { tap } from 'rxjs/internal/operators/tap';
import { catchError } from 'rxjs/internal/operators/catchError';
import { of } from 'rxjs/internal/observable/of';
import { InstallerService } from '../installer.service';

@Component({
    selector: 'app-install-action',
    templateUrl: './install-action.component.html',
    styleUrls: ['./install-action.component.scss']
})
export class InstallActionComponent implements OnInit, AfterViewInit {
    lang: any = LANG;
    steps: any[] = [];
    customId: string = '';

    // Workaround for angular component issue #13870
    disableAnimation = true;


    constructor(
        @Inject(MAT_DIALOG_DATA) public data: any,
        public dialogRef: MatDialogRef<InstallActionComponent>,
        public http: HttpClient,
        private installerService: InstallerService
    ) { }

    async ngOnInit(): Promise<void> {
        this.initSteps();

    }

    ngAfterViewInit(): void {
        setTimeout(() => this.disableAnimation = false);
    }

    async launchInstall() {
        for (let index = 0; index < this.data.length; index++) {
            this.steps[index].state = 'inProgress';
            await this.doStep(index);
        }
    }

    initSteps() {
        this.data.forEach((step: any, index: number) => {
            if (index === 0) {
                this.customId = step.body.customId;
            } else {
                step.body.customId = this.customId;
            }
            this.steps.push(
                {
                    idStep : step.idStep,
                    label: step.description,
                    state: '',
                    msgErr: '',
                }
            );
        });
    }

    doStep(index: number) {
        return new Promise((resolve, reject) => {
            console.log(this.steps[index]);
            if (this.installerService.isStepAlreadyLaunched(this.data[index].idStep)) {
                this.steps[index].state = 'OK';
                resolve(true);
            } else {
                this.http.post(this.data[index].route, this.data[index].body).pipe(
                    tap((data: any) => {
                        this.steps[index].state = 'OK';
                        this.installerService.setStep(this.steps[index]);
                        resolve(true);
                    }),
                    catchError((err: any) => {
                        this.steps[index].state = 'KO';
                        resolve(true);
                        this.steps[index].msgErr = err.error.errors;
                        return of(false);
                    })
                ).subscribe();
            }
        });
    }

    isInstallComplete() {
        return this.steps.filter(step => step.state === '').length === 0;
    }

    isInstallError() {
        return this.steps.filter(step => step.state === 'KO').length > 0;
    }
}
