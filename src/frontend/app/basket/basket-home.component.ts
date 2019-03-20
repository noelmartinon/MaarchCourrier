import { Component, OnInit, Input } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../translate.component';
import { MatSidenav } from '@angular/material';

declare function $j(selector: any) : any;
declare var angularGlobals : any;


@Component({
    selector: 'basket-home',
    templateUrl: "basket-home.component.html",
    styleUrls: ['basket-home.component.scss'],
})
export class BasketHomeComponent implements OnInit {

    lang: any = LANG;
    coreUrl : string;
    mobileMode                      : boolean   = false;
    
    @Input() homeData: any;
    @Input('currentBasketInfo') currentBasketInfo: any = {
        ownerId: 0,
        groupId: 0,
        basketId: ''
    };
    @Input() snavL: MatSidenav;

    constructor(public http: HttpClient) {
        this.mobileMode = angularGlobals.mobileMode;
    }

    ngOnInit(): void {        
        this.coreUrl = angularGlobals.coreUrl;
    }

    goTo(basketId:any, groupId:any) {
        window.location.href="index.php?page=view_baskets&fromV2=true&module=basket&baskets="+basketId+"&groupId="+groupId;
    }

    goToRedirect(basketId:any, owner:any, groupId:any) {
        window.location.href="index.php?page=view_baskets&fromV2=true&module=basket&baskets="+basketId+"_"+owner+"&groupId=" + groupId;
    }

    closePanelLeft() {
        if(this.mobileMode) {
            this.snavL.close();
        }
    }

    refreshBasketHome(){
        this.http.get(this.coreUrl + "rest/home")
            .subscribe((data: any) => {
                this.homeData = data;
            });
    }
}
