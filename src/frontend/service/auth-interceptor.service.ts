import { Injectable } from '@angular/core';
import { HttpHandler, HttpInterceptor, HttpRequest, HttpClient, HttpErrorResponse } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { catchError, filter, switchMap, take } from 'rxjs/operators';
import { NotificationService } from './notification/notification.service';
import { AuthService } from './auth.service';
import { Router } from '@angular/router';
import { BehaviorSubject, Observable } from 'rxjs';

@Injectable()
export class AuthInterceptor implements HttpInterceptor {
    byPassToken: any[] = [
        {
            route: '../rest/prerequisites',
            method: ['GET']
        },
        {
            route: '../rest/authenticate',
            method: ['POST']
        },
        {
            route: '../rest/authenticate/token',
            method: ['GET']
        },
        {
            route: '../rest/authenticationInformation',
            method: ['GET']
        },
        {
            route: '../rest/passwordRules',
            method: ['GET']
        },
        {
            route: '../rest/languages',
            method: ['GET']
        }
    ];
    byPassHandleErrors: any[] = [
        {
            route: '/password',
            method: ['PUT']
        }
    ];
    private isRefreshing = false;
    private refreshTokenSubject: BehaviorSubject<any> = new BehaviorSubject<any>(
        null
    );

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        private router: Router,
        public notificationService: NotificationService,
        public authService: AuthService,
    ) { }

    addAuthHeader(request: HttpRequest<any>) {

        const authHeader = this.authService.getToken();

        return request.clone({
            setHeaders: {
                'Authorization': 'Bearer ' + authHeader
            }
        });
    }

    intercept(request: HttpRequest<any>, next: HttpHandler): Observable<any> {
        if (this.byPassToken.filter(url => request.url.indexOf(url.route) > -1 && url.method.indexOf(request.method) > -1).length > 0) {
            return next.handle(request);
        } else {
            // Add current token in header request
            request = this.addAuthHeader(request);

            // Handle response
            return next.handle(request).pipe(
                /* map((data: any) => {
                  console.log('can modify datas for each response');
                  return data;
                }
                ),*/
                catchError(error => {
                    // Disconnect user if bad token process
                    if (this.byPassHandleErrors.filter(url => request.url.indexOf(url.route) > -1 && url.method.indexOf(request.method) > -1).length > 0) {
                        return next.handle(request);
                    } else if (error.status === 401) {
                        return this.handle401Error(request, next);
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

    private handle401Error(request: HttpRequest<any>, next: HttpHandler) {
        if (!this.isRefreshing) {
            this.isRefreshing = true;
            this.refreshTokenSubject.next(null);

            return this.authService.refreshToken().pipe(
                switchMap((data: any) => {
                    this.isRefreshing = false;
                    this.refreshTokenSubject.next(data.token);
                    request = this.addAuthHeader(request);
                    return next.handle(request);
                })
            );
        } else {
            return this.refreshTokenSubject.pipe(
                filter((token) => token != null),
                take(1),
                switchMap(() => {
                    request = this.addAuthHeader(request);
                    return next.handle(request);
                })
            );
        }
    }
}
