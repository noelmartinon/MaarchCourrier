import { Component, OnInit } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { Validators, FormGroup, FormBuilder } from '@angular/forms';
import { tap, catchError, finalize } from 'rxjs/operators';
import { AuthService } from '../../service/auth.service';
import { NotificationService } from '../notification.service';
import { environment } from '../../environments/environment';
import { LangService } from '../../service/app-lang.service';
import { of } from 'rxjs/internal/observable/of';
import { HeaderService } from '../../service/header.service';

@Component({
    templateUrl: 'login.component.html',
    styleUrls: ['login.component.scss'],
})
export class LoginComponent implements OnInit {
    lang: any = this.langService.getLang();
    loginForm: FormGroup;

    loading: boolean = false;
    showForm: boolean = false;
    environment: any;
    applicationName: string = '';
    loginMessage: string = '';

    constructor(
        private langService: LangService,
        private http: HttpClient,
        private router: Router,
        private headerService: HeaderService,
        public authService: AuthService,
        private notify: NotificationService,
        public dialog: MatDialog,
        private formBuilder: FormBuilder
    ) { }

    ngOnInit(): void {
        this.headerService.hideSideBar = true;
        this.loginForm = this.formBuilder.group({
            login: [null, Validators.required],
            password: [null, Validators.required]
        });

        this.environment = environment;
        if (this.authService.isAuth()) {
            this.router.navigate(['/home']);
        } else {
            this.getLoginInformations();
        }
    }

    onSubmit() {
        this.loading = true;
        this.http.post(
            '../../rest/authenticate',
            {
                'login': this.loginForm.get('login').value,
                'password': this.loginForm.get('password').value
            },
            {
                observe: 'response'
            }
        ).pipe(
            tap((data: any) => {
                this.authService.saveTokens(data.headers.get('Token'), data.headers.get('Refresh-Token'));
                this.authService.setUser({});
                this.router.navigate(['/home']);
            }),
            catchError((err: any) => {
                this.loading = false;
                if (err.status === 401) {
                    this.notify.error(this.lang.wrongLoginPassword);
                } else {
                    this.notify.handleSoftErrors(err);
                }

                return of(false);
            })
        ).subscribe();
    }

    getLoginInformations() {
        this.http.get('../../rest/authenticationInformations').pipe(
                tap((data: any) => {
                    this.applicationName = data.applicationName;
                    this.loginMessage = data.loginMessage;
                }),
                finalize(() => this.showForm = true),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
    }
}
