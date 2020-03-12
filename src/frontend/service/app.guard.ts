
import { Injectable } from '@angular/core';
import { ActivatedRouteSnapshot, CanActivate, Router, RouterStateSnapshot, CanDeactivate } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { Observable, of } from 'rxjs';
import { map, tap, catchError } from 'rxjs/operators';
import { HeaderService } from './header.service';
import { ProcessComponent } from '../app/process/process.component';
import { PrivilegeService } from './privileges.service';

@Injectable({
    providedIn: 'root'
})
export class AppGuard implements CanActivate {

    constructor(public http: HttpClient,
        private router: Router,
        public headerService: HeaderService,
        private privilegeService: PrivilegeService) { }

    canActivate(route: ActivatedRouteSnapshot, state: RouterStateSnapshot): Observable<boolean> | boolean {
        // TO DO : CAN BE REMOVE AFTER FULL V2
        localStorage.setItem('PreviousV2Route', state.url);
        this.headerService.resetSideNavSelection();
        if (route.url.filter((url: any) => url == 'signatureBook').length > 0) {
            this.headerService.hideSideBar = true;
        } else {       
            this.headerService.hideSideBar = false;
        }
        if (route.url.filter((url: any) => url == 'administration').length > 0 || route.url.filter((url: any) => url == 'profile').length > 0) { 
            this.headerService.sideBarAdmin = true;
        } else {
            this.headerService.sideBarAdmin = false;
        }
        
        if (this.headerService.user.id === undefined) {
            return this.http.get('../../rest/currentUser/profile')
                .pipe(
                    map((data: any) => {
                        this.headerService.user = {
                            id: data.id,
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
                        return true;
                    })
                );
        } else {
            return true;
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

        if ((component.isToolModified() && !component.isModalOpen()) || component.appDocumentViewer.isEditingTemplate()) {
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