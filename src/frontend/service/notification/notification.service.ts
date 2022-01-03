import { MatSnackBar } from '@angular/material/snack-bar';
import { Injectable, Component, Inject } from '@angular/core';
import { MAT_SNACK_BAR_DATA } from '@angular/material/snack-bar';
import { Router } from '@angular/router';
import { TranslateService } from '@ngx-translate/core';


@Component({
    selector: 'custom-snackbar',
    templateUrl: 'notification.service.html',
    styleUrls: ['notification.service.scss'],
})
export class CustomSnackbarComponent {
    constructor(@Inject(MAT_SNACK_BAR_DATA) public data: any,
        private notify: NotificationService,
    ) { }

    dismiss() {
        this.data.close();
    }
}

@Injectable()
export class NotificationService {

    constructor(public translate: TranslateService, private router: Router, public snackBar: MatSnackBar) {
    }
    success(message: string) {
        const duration = this.getMessageDuration(message, 2000);
        const snackBar = this.snackBar.openFromComponent(CustomSnackbarComponent, {
            duration: duration,
            panelClass: 'success-snackbar',
            verticalPosition : 'top',
            data: { message: message, icon: 'info-circle', close: () => {snackBar.dismiss()} }
        });
    }

    error(message: string, url: string = null) {
        const duration = this.getMessageDuration(message, 4000);
        const snackBar = this.snackBar.openFromComponent(CustomSnackbarComponent, {
            duration: duration,
            panelClass: 'error-snackbar',
            verticalPosition : 'top',
            data: { url: url, message: message, icon: 'exclamation-triangle', close: () => {snackBar.dismiss()} }
        });
    }

    handleErrors(err: any) {
        console.log(err);
        /*if (err.status === 401 && this.router.url !== '/home') {
            this.router.navigate(['/home']);
            window.location.reload(true);
            this.error(this.translate.instant('lang.mustReconnect'));
        } else*/ if (err.status === 0 && err.statusText === 'Unknown Error') {
            this.error(this.translate.instant('lang.connectionFailed'));
        } else {
            if (err.error !== undefined) {
                if (err.error.errors !== undefined) {
                    if (err.error.lang !== undefined) {
                        this.error(this.translate.instant('lang.' + err.error.lang));
                    } else if (err.error.errors === 'Document out of perimeter' || err.error.errors === 'Resource out of perimeter') {
                        //element a changer ??? 
                        console.debug('je suis bien ici', err)
                        this.error(this.translate.instant('lang.documentOutOfPerimeter'));
                    } else if (err.error.errors === 'Resources out of perimeter') {
                        this.error(this.translate.instant('lang.documentsOutOfPerimeter'));
                    } else {
                        this.error(err.error.errors, err.url);
                    }
                    if (err.status === 403 || err.status === 404) {
                        /*SGAMI-SO #75
                        if(err.sgami === 1 ){
                            console.debug('err.sgami', err.url)
                            this.router.navigate([err.url])                            
                        }else{*/
                            this.router.navigate(['/home']);
                        /*}
                        //SGAMI-SO #75 FIN*/
                       
                    }
                } else if (err.error.exception !== undefined) {
                    this.error(err.error.exception[0].message, err.url);
                } else if (err.error.error !== undefined) {
                    if (err.error.error[0] !== undefined) {
                        this.error(err.error.error[0].message, err.url);
                    } else {
                        this.error(err.error.error.message, err.url);
                    }
                } else {
                    this.error(`${err.status} : ${err.statusText}`, err.url);
                }
            } else {
                this.error(err);
            }
        }
    }

    handleSoftErrors(err: any) {
        console.log(err);
        if (err.error !== undefined) {
            if (err.error.errors !== undefined) {
                if (err.error.lang !== undefined) {
                    this.error(this.translate.instant('lang.' + err.error.lang));
                } else if (err.error.errors === 'Document out of perimeter' || err.error.errors === 'Resource out of perimeter') {
                    this.error(this.translate.instant('lang.documentOutOfPerimeter'));
                } else if (err.error.errors === 'Resources out of perimeter') {
                    this.error(this.translate.instant('lang.documentsOutOfPerimeter'));
                } else {
                    this.error(err.error.errors, err.url);
                }
            } else if (err.error.exception !== undefined) {
                this.error(err.error.exception[0].message, err.url);
            } else if (err.error.error !== undefined) {
                if (err.error.error[0] !== undefined) {
                    this.error(err.error.error[0].message, err.url);
                } else {
                    this.error(err.error.error.message, err.url);
                }
            } else {
                this.error(`${err.status} : ${err.statusText}`, err.url);
            }
        } else {
            this.error(err);
        }
    }

    getMessageDuration(message: string, minimumDuration: number) {
        const duration = (message.length / 25) * 1000;
        const maxDuration = 10000;
        if (duration < minimumDuration) {
            return minimumDuration;
        } else if (duration > maxDuration) {
            return maxDuration;
        }
        return duration;
    }
}
