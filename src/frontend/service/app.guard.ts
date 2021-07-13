
import { Injectable } from '@angular/core';
import { ActivatedRouteSnapshot, CanActivate, Router, RouterStateSnapshot } from '@angular/router';
import { HttpClient } from '@angular/common/http';

@Injectable({
    providedIn: 'root'
})
export class AppGuard implements CanActivate {

    constructor(public http: HttpClient, private router: Router) { }

    canActivate(route: ActivatedRouteSnapshot, state: RouterStateSnapshot) {

        // TO DO : CAN BE REMOVE AFTER FULL V2
        localStorage.setItem('PreviousV2Route', state.url);

        return true;
    }
}
