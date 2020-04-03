import { Component, OnInit, Input, EventEmitter, Output, ViewChild } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../translate.component';
import { MatSidenav } from '@angular/material/sidenav';
import { AppService } from '../../service/app.service';
import { tap, finalize, catchError } from 'rxjs/operators';
import { MatExpansionPanel } from '@angular/material/expansion';
import { of, Subscription } from 'rxjs';
import { NotificationService } from '../notification.service';
import { HeaderService } from '../../service/header.service';
import { ActionsService } from '../actions/actions.service';

declare function $j(selector: any) : any;

@Component({
    selector: 'basket-home',
    templateUrl: "basket-home.component.html",
    styleUrls: ['basket-home.component.scss'],
})
export class BasketHomeComponent implements OnInit {

    lang: any = LANG;
    loading: boolean = true;
    hoverEditGroupOrder: boolean = false;

    homeData: any = null;
    @Input('currentBasketInfo') currentBasketInfo: any = {
        ownerId: 0,
        groupId: 0,
        basketId: ''
    };
    @Input() snavL: MatSidenav;
    @Output('refreshEvent') refreshEvent = new EventEmitter<string>();
    @ViewChild('basketPanel', { static: true }) basketPanel: MatExpansionPanel;
    editOrderGroups: boolean = false;
    subscription: Subscription;

    constructor(
        public http: HttpClient,
        public appService: AppService,
        public headerService: HeaderService,
        private notify: NotificationService,
        public actionService: ActionsService,
    ) {

        this.subscription = this.actionService.catchAction().subscribe(message => {
            this.refreshBasketHome();
        });
    }

    ngOnInit(): void {
        this.getMyBaskets();
    }

    ngOnDestroy() {
        // unsubscribe to ensure no memory leaks
        this.subscription.unsubscribe();
    }

    getMyBaskets() {
        this.loading = true;

        this.http.get("../../rest/home").pipe(
            tap((data: any) => {
                this.homeData = data;
            }),
            finalize(() => {
                this.loading = false;
            })
        ).subscribe();
    }

    closePanelLeft() {
        if(this.appService.getViewMode()) {
            this.snavL.close();
        }
    }

    refreshDatas(basket: any) {
        this.refreshBasketHome();

        // AVOID DOUBLE REQUEST IF ANOTHER BASKET IS SELECTED
        if (this.currentBasketInfo.basketId == basket.id) {
            this.refreshEvent.emit();
        }
    }

    refreshBasketHome(){
        this.http.get("../../rest/home")
            .subscribe((data: any) => {
                this.homeData = data;
            });
    }

    togglePanel(state: boolean) {
        if (state) {
            this.basketPanel.open();
        } else {
            this.basketPanel.close();
        }
    }

    editGroupOrder() {
        this.editOrderGroups = !this.editOrderGroups;
    }

    updateGroupsOrder() {
        var groupsOrder = this.homeData.regroupedBaskets.map((element: any) => element.groupSerialId);
        this.http.put("../../rest/currentUser/profile/preferences", { homeGroups: groupsOrder }).pipe(
            tap(() => this.notify.success(this.lang.parameterUpdated)),
            catchError((err) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }
}
