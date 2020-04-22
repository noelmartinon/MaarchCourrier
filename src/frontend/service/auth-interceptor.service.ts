import { Injectable } from '@angular/core';
import { HttpHandler, HttpInterceptor, HttpRequest, HttpClient } from '@angular/common/http';
import { LANG } from '../app/translate.component';
import { catchError, switchMap } from 'rxjs/operators';
import { NotificationService } from '../app/notification.service';
import { AuthService } from './auth.service';
import { Observable } from 'rxjs/internal/Observable';
import { of } from 'rxjs/internal/observable/of';
import { Router } from '@angular/router';

@Injectable()
export class AuthInterceptor implements HttpInterceptor {
    lang: any = LANG;
    excludeUrls: any[] = [
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
            route: '../rest/password',
            method : ['GET']
        },
        {
            route: '../rest/passwordRules',
            method : ['GET']
        }
    ];
    constructor(
        public http: HttpClient,
        private router: Router,
        public notificationService: NotificationService,
        public authService: AuthService
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

        // We don't want to intercept some routes
        if ((this.excludeUrls.filter(url => url.route === request.url && url.method.indexOf(request.method) > -1).length > 0)) {
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
                    if (error.status === 401) {
                        console.log('Auth error !');
                        return this.http.get('../rest/authenticate/token', {
                            params: {
                                refreshToken: this.authService.getRefreshToken()
                            }
                        }).pipe(
                            switchMap((data: any) => {
                                console.log('Attempt get token ... !');
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
                                    console.log('Refresh Auth error !');
                                    this.logout();
                                }
                                return of(false);
                            })
                        );
                    } else if (error.status === 405) {
                        return this.router.navigate(['/password-modification']);
                    } else {
                        return next.handle(request);
                    }
                })
            );
        }
    }
}
