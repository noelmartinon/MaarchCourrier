import { Component, OnInit } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { Validators, FormGroup, FormBuilder } from '@angular/forms';
import { tap, catchError, finalize } from 'rxjs/operators';
import { AuthService } from '@service/auth.service';
import { NotificationService } from '@service/notification/notification.service';
import { environment } from '../../environments/environment';
import { of } from 'rxjs';
import { HeaderService } from '@service/header.service';
import { FunctionsService } from '@service/functions.service';
import { TimeLimitPipe } from '../../plugins/timeLimit.pipe';
import { AlertComponent } from '../../plugins/modal/alert.component';
import { TranslateService } from '@ngx-translate/core';
import { LocalStorageService } from '@service/local-storage.service';

@Component({
    templateUrl: 'login.component.html',
    styleUrls: ['login.component.scss'],
    providers: [TimeLimitPipe]
})
export class LoginComponent implements OnInit {
    loginForm: FormGroup;

    loading: boolean = false;
    showForm: boolean = false;
    environment: any;
    applicationName: string = '';
    loginMessage: string = '';

    constructor(
        public translate: TranslateService,
        private http: HttpClient,
        private router: Router,
        private headerService: HeaderService,
        public authService: AuthService,
        private localStorage: LocalStorageService,
        private functionsService: FunctionsService,
        private notify: NotificationService,
        public dialog: MatDialog,
        private formBuilder: FormBuilder,
        private timeLimit: TimeLimitPipe
    ) { }

    ngOnInit(): void {

        this.headerService.hideSideBar = true;
        this.loginForm = this.formBuilder.group({
            login: [null, Validators.required],
            password: [null, Validators.required]
        });

        this.environment = environment;
        if (this.authService.isAuth()) {
            if (!this.functionsService.empty(this.authService.getUrl(JSON.parse(atob(this.authService.getToken().split('.')[1])).user.id))) {
                this.router.navigate([this.authService.getUrl(JSON.parse(atob(this.authService.getToken().split('.')[1])).user.id)]);
            } else {
                this.router.navigate(['/home']);
            }
        } else {
            this.getLoginInformations();
        }
    }

    onSubmit(ssoToken = null) {
        this.loading = true;

        let url = '../rest/authenticate';

        if (ssoToken !== null) {
            url += ssoToken;
        }

        this.http.post(
            url,
            {
                'login': this.loginForm.get('login').value,
                'password': this.loginForm.get('password').value,
            },
            {
                observe: 'response'
            }
        ).pipe(
            tap((data: any) => {
                this.localStorage.resetLocal();
                this.authService.saveTokens(data.headers.get('Token'), data.headers.get('Refresh-Token'));
                this.authService.setUser({});
                if (this.authService.getCachedUrl()) {
                    this.router.navigateByUrl(this.authService.getCachedUrl());
                    this.authService.cleanCachedUrl();
                } else if (!this.functionsService.empty(this.authService.getUrl(JSON.parse(atob(data.headers.get('Token').split('.')[1])).user.id))) {
                    this.router.navigate([this.authService.getUrl(JSON.parse(atob(data.headers.get('Token').split('.')[1])).user.id)]);
                } else {
                    this.router.navigate(['/home']);
                }
            }),
            catchError((err: any) => {
                this.loading = false;
                if (err.error.errors === 'Authentication Failed') {
                    this.notify.error(this.translate.instant('lang.wrongLoginPassword'));
                } else if (err.error.errors === 'Account Locked') {
                    this.notify.error(this.translate.instant('lang.accountLocked') + ' ' + this.timeLimit.transform(err.error.date));
                } else {
                    this.notify.handleSoftErrors(err);
                }
                return of(false);
            })
        ).subscribe();
    }

    getLoginInformations() {
        this.http.get('../rest/authenticationInformations').pipe(
            tap((data: any) => {
                this.authService.setAppSession(data.instanceId);
                this.authService.changeKey = data.changeKey;
                this.applicationName = data.applicationName;
                this.loginMessage = data.loginMessage;
                this.authService.setEvent('authenticationInformations');
                this.authService.authMode = data.authMode;
                this.authService.authUri = data.authUri;

                this.initConnection();
            }),
            finalize(() => this.showForm = true),
            catchError((err: any) => {
                this.http.get('../rest/validUrl').pipe(
                    tap((data: any) => {
                        if (!this.functionsService.empty(data.url)) {
                            window.location.href = data.url;
                        } else if (data.lang === 'moreOneCustom') {
                            this.dialog.open(AlertComponent, { panelClass: 'maarch-modal', autoFocus: false, disableClose: true, data: { title: this.translate.instant('lang.accessNotFound'), msg: this.translate.instant('lang.moreOneCustom'), hideButton: true } });
                        } else if (data.lang === 'noConfiguration') {
                            this.router.navigate(['/install']);
                        } else {
                            this.notify.handleSoftErrors(err);
                        }
                    })
                ).subscribe();
                return of(false);
            })
        ).subscribe();
    }

    initConnection() {
        if (['cas', 'keycloak'].indexOf(this.authService.authMode) > -1) {
            this.loginForm.disable();
            this.loginForm.setValidators(null);
            const regex = /ticket=[.]*/g;
            if (window.location.search.match(regex) !== null) {
                const ssoToken = window.location.search.substring(1, window.location.search.length);
                window.history.replaceState({}, document.title, window.location.pathname + window.location.hash);
                this.onSubmit(`?${ssoToken}`);
            } else {
                window.location.href = this.authService.authUri;
            }
        }
    }
}
