import { Component, OnInit, Inject } from '@angular/core';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { LANG } from '../../../app/translate.component';
import { HttpClient } from '@angular/common/http';
import { tap } from 'rxjs/internal/operators/tap';
import { catchError } from 'rxjs/internal/operators/catchError';
import { of } from 'rxjs/internal/observable/of';

@Component({
    selector: 'app-install-action',
    templateUrl: './install-action.component.html',
    styleUrls: ['./install-action.component.scss']
})
export class InstallActionComponent implements OnInit {
    lang: any = LANG;
    steps: any[] = [];
    customId: string = '';

    constructor(@Inject(MAT_DIALOG_DATA) public data: any, public dialogRef: MatDialogRef<InstallActionComponent>, public http: HttpClient) { }

    async ngOnInit(): Promise<void> {
        this.initSteps();

        for (let index = 0; index < this.data.length; index++) {
            this.steps[index].state = 'inProgress';
            await this.doStep(index);
        }
    }

    initSteps() {
        this.data.forEach((step: any, index: number) => {
            if (index === 0) {
                this.customId = step.body.customName;
            } else {
                step.body.customName = this.customId;
            }
            this.steps.push(
                {
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
            this.http.post(this.data[index].route, this.data[index].body).pipe(
                tap((data: any) => {
                    this.steps[index].state = 'OK';
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.steps[index].state = 'KO';
                    resolve(true);
                    this.steps[index].msgErr = err.error.errors;
                    return of(false);
                })
            ).subscribe();
        });
    }

    isInstallComplete() {
        return this.steps.filter(step => step.state === '' ).length === 0;
    }

    isInstallError() {
        return this.steps.filter(step => step.state === 'KO' ).length > 0;
    }
}
