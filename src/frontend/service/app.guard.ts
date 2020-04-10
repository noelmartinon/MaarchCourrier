
import { Injectable } from '@angular/core';
import { ActivatedRouteSnapshot, CanActivate, Router, RouterStateSnapshot, CanDeactivate } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { Observable, of } from 'rxjs';
import { map, tap, catchError, exhaustMap } from 'rxjs/operators';
import { HeaderService } from './header.service';
import { ProcessComponent } from '../app/process/process.component';
import { PrivilegeService } from './privileges.service';
import { AuthService } from './auth.service';
import { LocalStorageService } from './local-storage.service';

@Injectable({
    providedIn: 'root'
})
export class AppGuard implements CanActivate {

    constructor(
        public http: HttpClient,
        private router: Router,
        private authService: AuthService,
        private localStorage: LocalStorageService,
        public headerService: HeaderService,
        private privilegeService: PrivilegeService
    ) { }

    canActivate(route: ActivatedRouteSnapshot, state: RouterStateSnapshot): any {
        console.log('guard');

        this.headerService.resetSideNavSelection();

        let tokenInfo = this.authService.getToken();

        if (tokenInfo !== null) {
            console.log('Token trouvé !');

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

                            this.headerService.nbResourcesFollowed = data.nbFollowedResources;
                            this.privilegeService.resfreshUserShortcuts();

                            if (this.headerService.user.status === 'ABS') {
                                console.log('ABS!');
                                return this.router.navigate(['/activate-user']);
                            } else {
                                if (route.url.map((url: any) => url.path).filter((url: any) => url === 'signatureBook').length > 0) {
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

                        })
                    );
            } else {
                console.log('Données user trouvé !');

                if (this.headerService.user.status === 'ABS') {
                    console.log('ABS!');
                    return this.router.navigate(['/activate-user']);
                } else {
                    if (route.url.map((url: any) => url.path).filter((url: any) => url === 'signatureBook').length > 0) {
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
            }
        } else {
            console.log('Aucun token trouvé ! Recupération du token ...');

            return this.http.get('../rest/authenticationInformations')
                .pipe(
                    map((data: any) => {
                        this.authService.authMode = data.connection;
                        this.authService.changeKey = data.changeKey;
                        this.localStorage.setAppSession(data.instanceId);
                        tokenInfo = this.authService.getToken();

                        if (tokenInfo !== null) {
                            this.authService.setUrl(route.url.join('/'));
                            console.log('Token trouvé !');
                            console.log('Récupération données user...');
                            this.http.get('../rest/currentUser/profile')
                                .pipe(
                                    map((dataUser: any) => {
                                        this.headerService.user = {
                                            id: dataUser.id,
                                            status: data.status,
                                            userId: dataUser.user_id,
                                            firstname: dataUser.firstname,
                                            lastname: dataUser.lastname,
                                            entities: dataUser.entities,
                                            groups: dataUser.groups,
                                            preferences: dataUser.preferences,
                                            privileges: dataUser.privileges[0] === 'ALL_PRIVILEGES' ? this.privilegeService.getAllPrivileges() : dataUser.privileges
                                        };

                                        this.headerService.nbResourcesFollowed = dataUser.nbFollowedResources;
                                        this.privilegeService.resfreshUserShortcuts();
                                        if (this.headerService.user.status === 'ABS') {
                                            console.log('ABS!');
                                            return this.router.navigate(['/activate-user']);
                                        } else {
                                            if (route.url.map((url: any) => url.path).filter((url: any) => url === 'signatureBook').length > 0) {
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
                                    })
                                ).subscribe();
                            return true;
                        } else {
                            console.log('Aucun token trouvé ! Redirection sur login ...');
                            this.authService.logout();
                            return false;
                        }
                    }),
                );

        }
    }
}

@Injectable({
    providedIn: 'root'
})
export class AfterProcessGuard implements CanDeactivate<ProcessComponent> {
    constructor() { }

    async canDeactivate(component: ProcessComponent): Promise<boolean> {
        if (!component.isActionEnded() && !component.detailMode) {
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