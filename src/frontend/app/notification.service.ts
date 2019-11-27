import { MatSnackBar } from '@angular/material';
import { Injectable, Component, Inject } from '@angular/core';
import { MAT_SNACK_BAR_DATA } from '@angular/material';
import { Router } from '@angular/router';
import { LANG } from './translate.component';
@Component({
    selector: 'custom-snackbar',
    template: '<mat-grid-list cols="4" rowHeight="1:1"><mat-grid-tile colspan="1"><mat-icon class="fa fa-{{data.icon}} fa-2x"></mat-icon></mat-grid-tile><mat-grid-tile colspan="3">{{data.message}}</mat-grid-tile></mat-grid-list>' // You may also use a HTML file
})
export class CustomSnackbarComponent {
    constructor(@Inject(MAT_SNACK_BAR_DATA) public data: any) { }
}

@Injectable()
export class NotificationService {
    lang: any = LANG;

    constructor(private router: Router, public snackBar: MatSnackBar) {
    }
    success(message: string) {
        this.snackBar.openFromComponent(CustomSnackbarComponent, {
            duration: 2000,
            data: { message: message, icon: 'info-circle' }
        });
    }

    error(message: string) {
        this.snackBar.openFromComponent(CustomSnackbarComponent, {
            duration: 2000,
            data: { message: message, icon: 'exclamation-triangle' }
        });
    }

    handleErrors(err: any) {
        console.log(err);
        if (err.status === 401 && this.router.url !== '/home') {
            this.router.navigate(['/home']);
            window.location.reload(true);
            this.error(this.lang.mustReconnect);
        } else if (err.status === 0 && err.statusText === 'Unknown Error') {
            this.error(this.lang.connectionFailed);
        } else {
            if (err.error !== undefined) {
                if (err.error.errors !== undefined) {
                    if (err.error.lang !== undefined) {
                        this.error('lang.' + err.error.lang);
                    } else {
                        this.error(err.error.errors);
                    }
                    if (err.status === 403 || err.status === 404) {
                        this.router.navigate(['/home']);
                    }
                } else if (err.error.exception !== undefined) {
                    this.error(err.error.exception[0].message);
                } else if(err.error.error !== undefined){
                    this.error(err.error.error.message);
                } else {
                    this.error(err.status + ' : ' + err.statusText);
                }
            } else {
                this.error(err);
            }
        }
    }
}
