import { HttpClient } from '@angular/common/http';
import { Component, Inject, OnInit } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { TranslateService } from '@ngx-translate/core';
import { of } from 'rxjs';
import { catchError, finalize, tap } from 'rxjs/operators';

@Component({
    templateUrl: 'check-sae-interconnection.component.html',
    styleUrls: ['check-sae-interconnection.component.scss'],
})

export class CheckSaeInterconnectionComponent implements OnInit {

    loading: boolean = true;
    hasError: boolean = false;
    isSae: boolean = false;
    archivalError: string = '';
    result: string  = this.translate.instant('lang.loadingTest');


    constructor (
        public http: HttpClient,
        public translate: TranslateService,
        public dialogRef: MatDialogRef<CheckSaeInterconnectionComponent>,
        @Inject(MAT_DIALOG_DATA) public data: any,
    ) { }

    ngOnInit() {
        this.loading = true;
        setTimeout(() => {
            this.checkInterconnection();
        }, 500);
    }

    checkInterconnection() {
        this.http.get('../rest/archival/retentionRules').pipe(
            tap(() => {
                this.loading = false;
                this.hasError = false;
                this.result = '<b>' + this.translate.instant('lang.interconnectionSuccess') + '</b> ';
            }),
            catchError((err: any) => {
                this.hasError = true;
                this.loading = false;
                this.archivalError = err.error.errors;
                const index: number = this.archivalError.indexOf(':');
                this.archivalError = `(${this.archivalError.slice(index + 1, this.archivalError.length).replace(/^[\s]/, '')})`;
                this.result = '<b>' + this.translate.instant('lang.interconnectionFailed') + '</b> ' + this.archivalError;
                return of(false);
            })
        ).subscribe();
    }
}
