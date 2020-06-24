import { Injectable } from '@angular/core';

@Injectable({
    providedIn: 'root'
})
export class AppService {

    private _mobileQueryListener: () => void;
    mobileQuery: MediaQueryList;

    currentUser: any;

    constructor() {
        /*this.mobileQuery = media.matchMedia('(max-width: 768px)');
        this._mobileQueryListener = () => changeDetectorRef.detectChanges();
        this.mobileQuery.addListener(this._mobileQueryListener);*/
    }

    getViewMode() {
        return false;
        if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
            return true;
        } else {
            return this.mobileQuery.matches;
        }
    }

    /*ngOnDestroy(): void {
        this.mobileQuery.removeListener(this._mobileQueryListener);
    }*/
}
