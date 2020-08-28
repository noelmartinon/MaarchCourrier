import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { tap } from 'rxjs/internal/operators/tap';
import { catchError, finalize } from 'rxjs/operators';
import { of } from 'rxjs/internal/observable/of';
import { NotificationService } from './notification/notification.service';
import { AuthService } from './auth.service';
import { AlertComponent } from '../plugins/modal/alert.component';
import { MatDialog } from '@angular/material/dialog';
import { TranslateService } from '@ngx-translate/core';

declare var $: any;

@Injectable({
    providedIn: 'root'
})
export class AppService {

    screenWidth: number = 1920;

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        private notify: NotificationService,
        public authService: AuthService,
        private dialog: MatDialog,
    ) { }

    getViewMode() {
        if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
            return true;
        } else {
            return this.screenWidth <= 768;
        }
    }

    setScreenWidth(width: number) {
        this.screenWidth = width;
    }

    applyMinorUpdate() {
        const loader = '<div id="updateLoading" style="position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);width: 200px;text-align: center;"><img src="assets/spinner.gif"></div>';
        $('body').append(loader);
        return new Promise((resolve) => {
            this.http.put('../rest/versionsUpdateSQL', {}).pipe(
                tap((data: any) => {
                    resolve(true);
                    $('#updateLoading').remove();
                }),
                finalize(() => $('#updateLoading').remove()),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    resolve(true);
                    return of(false);
                })
            ).subscribe();
        });
    }

    checkAppSecurity() {
        this.authService.catchEvent().subscribe((result: any) => {
            if (result === 'authenticationInformations') {
                if (this.authService.changeKey) {
                    setTimeout(() => {
                        this.dialog.open(AlertComponent, {
                            panelClass: 'maarch-modal',
                            autoFocus: false,
                            disableClose: true,
                            data: {
                                mode: 'danger',
                                title: this.translate.instant('lang.warnPrivateKeyTitle'),
                                msg: this.translate.instant('lang.warnPrivateKey')
                            }
                        });
                    }, 1000);
                }
            }
        });
    }
}
