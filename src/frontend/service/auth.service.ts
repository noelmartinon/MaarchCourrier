import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { LocalStorageService } from './local-storage.service';
import { NotificationService } from '../app/notification.service';
import { HeaderService } from './header.service';

@Injectable({
    providedIn: 'root'
})
export class AuthService {

    authMode: string = 'default';
    changeKey: boolean = false;
    user: any = {};

    constructor(public http: HttpClient,
        private router: Router,
        private headerService: HeaderService,
        private notify: NotificationService,
        private localStorage: LocalStorageService) { }

    getToken() {
        return this.localStorage.get('MaarchCourrierToken');
    }

    getAppSession() {
        return this.localStorage.getAppSession();
    }

    setAppSession(id: string) {
        this.localStorage.setAppSession(id);
    }

    setCachedUrl(url: string) {
        this.localStorage.save('MaarchCourrierCachedUrl', url);
    }

    getCachedUrl() {
        return this.localStorage.get('MaarchCourrierCachedUrl');
    }

    cleanCachedUrl() {
        return this.localStorage.remove(`MaarchCourrierCachedUrl`);
    }

    getUrl(id: number) {
        return this.localStorage.get(`MaarchCourrierUrl_${id}`);
    }

    setUrl(url: string) {
        const arrUrl = url.split('/');

        if (arrUrl.indexOf('resources') === -1 && arrUrl.indexOf('content') === -1) {
            this.localStorage.save(`MaarchCourrierUrl_${JSON.parse(atob(this.getToken().split('.')[1])).user.id}`, url);
        }
    }

    cleanUrl(id: number) {
        return this.localStorage.remove(`MaarchCourrierUrl_${id}`);
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

    async logout(cleanUrl: boolean = true) {
        if ( this.getToken() !== null && cleanUrl) {
            this.cleanUrl(JSON.parse(atob(this.getToken().split('.')[1])).user.id);
        }
        this.headerService.setUser();
        await this.router.navigate(['/login']);
        this.clearTokens();
    }

    saveTokens(token: string, refreshToken: string) {
        this.setToken(token);
        this.setRefreshToken(refreshToken);
    }

    isAuth(): boolean {
        return this.getToken() !== null;
    }

    updateUserInfo(token: string) {
        const currentPicture  = this.user.picture;

        this.user = JSON.parse(atob(token.split('.')[1])).user;

        this.user.picture = currentPicture;
    }

    updateUserInfoWithTokenRefresh() {
        this.http.get('../rest/authenticate/token', {
            params: {
              refreshToken: this.getRefreshToken()
            }
          }).subscribe({
            next: (data: any) => {
                this.setToken(data.token);

                this.updateUserInfo(this.getToken());
            },
            error: err => {
                this.notify.handleSoftErrors(err);
            }
        });
    }

    setUser(value: any) {
        this.user = value;
    }
}
