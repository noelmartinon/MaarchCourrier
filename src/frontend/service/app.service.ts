import { Injectable } from '@angular/core';

@Injectable({
    providedIn: 'root'
})
export class AppService {

    screenWidth: number = 1920;

    constructor() { }

    getViewMode() {
        if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
            return true;
        } else {
            return this.screenWidth <= 768;
        }
    }

    setScreenWidth(width: number) {
        this.screenWidth = width;
    }
}
