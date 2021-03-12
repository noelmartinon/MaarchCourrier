import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LocalStorageService } from './local-storage.service';
import { NotificationService } from './notification/notification.service';
import { of } from 'rxjs';
import { catchError, tap } from 'rxjs/operators';
// import { TranslateService } from '@ngx-translate/core';

@Injectable({
    providedIn: 'root'
})
export class AuthService {

    applicationName: string = 'Maarch Courrier';
    user: any = {};

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        private localStorage: LocalStorageService,
        // public translate: TranslateService,
    ) { }

    getToken() {
        return this.localStorage.get('MaarchCourrierToken');
    }

    getAppSession() {
        return this.localStorage.getAppSession();
    }

    setAppSession(id: string) {
        this.localStorage.setAppSession(id);
    }

    setToken(token: string) {
        this.localStorage.save('MaarchCourrierToken', token);
    }

    getRefreshToken() {
        return this.localStorage.get('MaarchCourrierRefreshToken');
    }

    setRefreshToken(refreshToken: string) {
        this.localStorage.save('MaarchCourrierRefreshToken', refreshToken);
    }

    clearTokens() {
        this.localStorage.remove('MaarchCourrierToken');
        this.localStorage.remove('MaarchCourrierRefreshToken');
    }

    getAppInfo() {
        return new Promise((resolve) => {
            this.http.get('../rest/authenticationInformations')
        .pipe(
            tap((data: any) => {
                console.log(data);
                this.applicationName = data.applicationName;
                this.setAppSession(data.instanceId);
                this.updateUserInfo(this.getToken());
                resolve(true)
            }),
            catchError((err: any) => {
                console.log(err);
                return of(false);
            })
        ).subscribe();
        });
    }

    refreshToken() {
        return this.http
            .get<any>(`../rest/authenticate/token`, { params: { refreshToken: this.getRefreshToken() } })
            .pipe(
                tap((data) => {
                    // Update stored token
                    this.setToken(data.token);

                    // Update user info
                    this.updateUserInfo(data.token);
                }),
                catchError((error) => {
                    // this.notify.error(this.translate.instant('lang.sessionExpired'));
                    return of(false);
                })
            );
    }

    saveTokens(token: string, refreshToken: string) {
        this.setToken(token);
        this.setRefreshToken(refreshToken);
    }

    isAuth(): boolean {
        return this.user.id !== undefined;
    }

    updateUserInfo(token: string) {
        this.user = JSON.parse(atob(token.split('.')[1])).user;
    }
}
