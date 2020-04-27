
import { Injectable } from '@angular/core';
import { ActivatedRouteSnapshot, CanActivate, Router, RouterStateSnapshot, CanDeactivate, UrlTree } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { Observable, of } from 'rxjs';
import { map, tap, catchError, exhaustMap, filter } from 'rxjs/operators';
import { HeaderService } from './header.service';
import { ProcessComponent } from '../app/process/process.component';
import { PrivilegeService } from './privileges.service';
import { AuthService } from './auth.service';
import { LocalStorageService } from './local-storage.service';
import { FunctionsService } from './functions.service';

@Injectable({
    providedIn: 'root'
})
export class AppGuard implements CanActivate {

    constructor(
        public http: HttpClient,
        private router: Router,
        private authService: AuthService,
        private localStorage: LocalStorageService,
        private functionService: FunctionsService,
        public headerService: HeaderService,
        private privilegeService: PrivilegeService
    ) { }

    canActivate(route: ActivatedRouteSnapshot, state: RouterStateSnapshot): Observable<any> {
        console.clear();
        console.log('== ROUTE GUARD ==');
        this.headerService.resetSideNavSelection();

        let tokenInfo = this.authService.getToken();

        if (tokenInfo !== null) {
            this.functionService.debug('Token trouvé !', route.url.join('/'));

            this.authService.setUrl(route.url.join('/'));
            if (this.headerService.user.id === undefined) {
                console.log('Récupération données user...');
                return this.http.get('../rest/currentUser/profile')
                    .pipe(
                        map((data: any) => {
                            this.headerService.user = {
                                id: data.id,
                                status: data.status,
                                userId: data.user_id,
                                firstname: data.firstname,
                                lastname: data.lastname,
                                entities: data.entities,
                                groups: data.groups,
                                preferences: data.preferences,
                                privileges: data.privileges[0] === 'ALL_PRIVILEGES' ? this.privilegeService.getAllPrivileges() : data.privileges
                            };
                            this.functionService.debug('', route.url.join('/'));
                            this.headerService.nbResourcesFollowed = data.nbFollowedResources;
                            this.privilegeService.resfreshUserShortcuts();

                            if (this.headerService.user.status === 'ABS') {
                                return this.router.navigate(['/activate-user']);
                            } else {
                                if (route.url.map((url: any) => url.path).filter((url: any) => ['signatureBook', 'content'].indexOf(url) > -1).length > 0) {
                                    this.headerService.hideSideBar = true;
                                } else {
                                    this.headerService.hideSideBar = false;
                                }
                                if (route.url.map((url: any) => url.path).filter((url: any) => url === 'administration').length > 0 || route.url.map((url: any) => url.path).filter((url: any) => url === 'profile').length > 0) {
                                    this.headerService.sideBarAdmin = true;
                                } else {
                                    this.headerService.sideBarAdmin = false;
                                }
                                return true;
                            }

                        }),
                        catchError((err: any) => {
                            console.log(err);
                            if (err.error.errors === 'User must change his password') {
                                return this.router.navigate(['/password-modification']);
                            } else {
                                return of(false);
                            }
                        })
                    );
            } else {
                console.log('Données user trouvées !');

                if (this.headerService.user.status === 'ABS') {
                    this.router.navigate(['/activate-user']);
                    return of(false);
                } else {
                    if (route.url.map((url: any) => url.path).filter((url: any) => ['signatureBook', 'content'].indexOf(url) > -1).length > 0) {
                        this.headerService.hideSideBar = true;
                    } else {
                        this.headerService.hideSideBar = false;
                    }
                    if (route.url.map((url: any) => url.path).filter((url: any) => url === 'administration').length > 0 || route.url.map((url: any) => url.path).filter((url: any) => url === 'profile').length > 0) {
                        this.headerService.sideBarAdmin = true;
                    } else {
                        this.headerService.sideBarAdmin = false;
                    }
                    return of(true);
                }
            }
        } else {
            console.log('Aucun token trouvé ! Recupération du token ...');

            return this.http.get('../rest/authenticationInformations')
                .pipe(
                    map((data: any) => {
                        this.authService.setAppSession(data.instanceId);
                        // this.authService.authMode = data.connection;
                        // this.authService.changeKey = data.changeKey;
                        this.localStorage.setAppSession(data.instanceId);
                        tokenInfo = this.authService.getToken();

                        if (tokenInfo !== null) {
                            this.authService.setUrl(route.url.join('/'));
                            this.functionService.debug('', route.url.join('/'));
                            return tokenInfo;
                        } else {
                            this.authService.setCachedUrl(state.url.replace(/^\//g, ''));
                            console.log('Aucun token trouvé ! Redirection sur login ...');
                            this.authService.logout(false);
                            return false;
                        }
                    }),
                    filter((info: any) => info !== null),
                    tap(() => console.log('Récupération données user...')),
                    exhaustMap(() => this.http.get('../rest/currentUser/profile')),
                    map((dataUser: any) => {
                        this.headerService.user = {
                            id: dataUser.id,
                            status: dataUser.status,
                            userId: dataUser.user_id,
                            firstname: dataUser.firstname,
                            lastname: dataUser.lastname,
                            entities: dataUser.entities,
                            groups: dataUser.groups,
                            preferences: dataUser.preferences,
                            privileges: dataUser.privileges[0] === 'ALL_PRIVILEGES' ? this.privilegeService.getAllPrivileges() : dataUser.privileges
                        };
                        this.functionService.debug('', route.url.join('/'));
                        this.headerService.nbResourcesFollowed = dataUser.nbFollowedResources;
                        this.privilegeService.resfreshUserShortcuts();
                        if (this.headerService.user.status === 'ABS') {
                            return this.router.navigate(['/activate-user']);
                        } else {
                            if (route.url.map((url: any) => url.path).filter((url: any) => ['signatureBook', 'content'].indexOf(url) > -1).length > 0) {
                                this.headerService.hideSideBar = true;
                            } else {
                                this.headerService.hideSideBar = false;
                            }
                            if (route.url.map((url: any) => url.path).filter((url: any) => url === 'administration').length > 0 || route.url.map((url: any) => url.path).filter((url: any) => url === 'profile').length > 0) {
                                this.headerService.sideBarAdmin = true;
                            } else {
                                this.headerService.sideBarAdmin = false;
                            }
                            return true;
                        }
                    }),
                    catchError((err: any) => {
                        console.log(err);
                        if (err.error.errors === 'User must change his password') {
                            return this.router.navigate(['/password-modification']);
                        } else {
                            return of(false);
                        }
                    })
                );

        }
    }
}

@Injectable({
    providedIn: 'root'
})
export class AfterProcessGuard implements CanDeactivate<ProcessComponent> {
    constructor() { }

    async canDeactivate(component: ProcessComponent, currentRoute: ActivatedRouteSnapshot, currentState: RouterStateSnapshot, nextState: RouterStateSnapshot): Promise<boolean> {
        if (nextState.url != '/login' && !component.isActionEnded() && !component.detailMode) {
            component.unlockResource();
        }

        if ((component.isToolModified() && !component.isModalOpen()) || (component.appDocumentViewer !== undefined && component.appDocumentViewer.isEditingTemplate())) {
            if (confirm(component.lang.saveModifiedData)) {
                await component.saveModificationBeforeClose();
            }
        }
        /*if(component.hasUnsavedData()){
            if (confirm("You have unsaved changes! If you leave, your changes will be lost.")) {
                return true;
            } else {
                return false;
            }
        }*/
        return true;
    }
}