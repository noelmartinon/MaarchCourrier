import { Injectable } from '@angular/core';
import { HttpHandler, HttpInterceptor, HttpRequest, HttpClient, HttpErrorResponse } from '@angular/common/http';
import { LANG } from '../app/translate.component';
import { catchError, switchMap } from 'rxjs/operators';
import { NotificationService } from './notification/notification.service';
import { AuthService } from './auth.service';
import { Observable } from 'rxjs/internal/Observable';
import { of } from 'rxjs/internal/observable/of';
import { Router } from '@angular/router';
import { FunctionsService } from './functions.service';

@Injectable()
export class AuthInterceptor implements HttpInterceptor {
    lang: any = LANG;
    byPassToken: any[] = [
        {
            route: '../rest/prerequisites',
            method : ['GET']
        },
        {
            route: '../rest/authenticate',
            method : ['POST']
        },
        {
            route: '../rest/authenticate/token',
            method : ['GET']
        },
        {
            route: '../rest/authenticationInformation',
            method : ['GET']
        },
        {
            route: '../rest/passwordRules',
            method : ['GET']
        },
        {
            route: '../rest/languages',
            method : ['GET']
        }
    ];
    byPassHandleErrors: any[] = [
        {
            route: '/password',
            method : ['PUT']
        }
    ];
    constructor(
        public http: HttpClient,
        private router: Router,
        public notificationService: NotificationService,
        public authService: AuthService,
        private functionsService: FunctionsService
    ) { }

    addAuthHeader(request: HttpRequest<any>) {

        const authHeader = this.authService.getToken();

        return request.clone({
            setHeaders: {
                'Authorization': 'Bearer ' + authHeader
            }
        });
    }

    logout() {
        this.authService.logout(false);
        this.notificationService.error(this.lang.sessionExpired);
    }

    intercept(request: HttpRequest<any>, next: HttpHandler): Observable<any> {

        if (this.byPassToken.filter(url => request.url.indexOf(url.route) > -1 && url.method.indexOf(request.method) > -1).length > 0) {
            return next.handle(request);
        } else {
            // Add current token in header request
            request = this.addAuthHeader(request);

            // Handle response
            return next.handle(request).pipe(
                /*map((data: any) => {
                  console.log('can modify datas for each response');
                  return data;
                }
                ),*/
                catchError(error => {
                    // Disconnect user if bad token process
                    if (this.byPassHandleErrors.filter(url => request.url.indexOf(url.route) > -1 && url.method.indexOf(request.method) > -1).length > 0) {
                        return next.handle(request);
                    } else if (error.status === 401) {
                        this.functionsService.debug('Auth error', request.url);
                        return this.http.get('../rest/authenticate/token', {
                            params: {
                                refreshToken: this.authService.getRefreshToken()
                            }
                        }).pipe(
                            switchMap((data: any) => {
                                this.functionsService.debug('Attempt get token ... !', request.url);
                                // Update stored token
                                this.authService.setToken(data.token);

                                // Update user info
                                this.authService.updateUserInfo(data.token);

                                // Clone our request with token updated ant try to resend it
                                request = this.addAuthHeader(request);

                                return next.handle(request).pipe(
                                    catchError(err => {
                                        // Disconnect user if bad token process
                                        if (err.status === 401) {
                                            this.logout();
                                            return of(false);
                                        }
                                    })
                                );
                            }
                            ),
                            catchError(err => {
                                // Disconnect user if bad token process
                                if (err.status === 401) {
                                    this.functionsService.debug('Refresh token failed !', request.url);
                                    this.logout();
                                }
                                return of(false);
                            })
                        );
                    } else if (error.error.errors === 'User must change his password') {
                        return this.router.navigate(['/password-modification']);
                    } else {
                        const response = new HttpErrorResponse({
                            error: error.error,
                            status: error.status,
                            statusText: error.statusText,
                            headers: error.headers,
                            url: error.url,
                        });
                        return Promise.reject(response);
                    }
                })
            );
        }
    }
}
