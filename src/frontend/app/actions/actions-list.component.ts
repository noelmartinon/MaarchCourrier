import { Component, OnInit, Input, ViewChild, Output, EventEmitter } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../translate.component';
import { NotificationService } from '../notification.service';
import { MatDialog, MatMenuTrigger } from '@angular/material';

import { ConfirmActionComponent } from './confirm-action/confirm-action.component';
import { EnabledBasketPersistenceActionComponent } from './enabled-basket-persistence-action/enabled-basket-persistence-action.component';
import { DisabledBasketPersistenceActionComponent } from './disabled-basket-persistence-action/disabled-basket-persistence-action.component';
import { ResMarkAsReadActionComponent } from './res-mark-as-read-action/res-mark-as-read-action.component';
import { CloseMailActionComponent } from './close-mail-action/close-mail-action.component';
import { UpdateAcknowledgementSendDateActionComponent } from './update-acknowledgement-send-date-action/update-acknowledgement-send-date-action.component';
import { CreateAcknowledgementReceiptActionComponent } from './create-acknowledgement-receipt-action/create-acknowledgement-receipt-action.component';
import { CloseAndIndexActionComponent } from './close-and-index-action/close-and-index-action.component';
import { UpdateDepartureDateActionComponent } from './update-departure-date-action/update-departure-date-action.component';
import { SendExternalSignatoryBookActionComponent } from './send-external-signatory-book-action/send-external-signatory-book-action.component';
import { SendExternalNoteBookActionComponent } from './send-external-note-book-action/send-external-note-book-action.component';
// import { ProcessActionComponent } from './process-action/process-action.component';
import { Router } from '@angular/router';
import { ViewDocActionComponent } from './view-doc-action/view-doc-action.component';
import { RedirectActionComponent } from './redirect-action/redirect-action.component';
import { SendShippingActionComponent } from './send-shipping-action/send-shipping-action.component';

@Component({
    selector: 'app-actions-list',
    templateUrl: "actions-list.component.html",
    styleUrls: ['actions-list.component.scss'],
    providers: [NotificationService],
})
export class ActionsListComponent implements OnInit {

    lang: any = LANG;
    loading: boolean = false;

    @ViewChild(MatMenuTrigger) contextMenu: MatMenuTrigger;
    @Output() triggerEvent = new EventEmitter<string>();

    contextMenuPosition = { x: '0px', y: '0px' };
    contextMenuTitle = '';
    currentAction: any = {};
    basketInfo: any = {};
    contextResId = 0;
    currentLock: any = null;
    arrRes: any[] = [];

    actionsList: any[] = [];

    @Input('selectedRes') selectedRes: any;
    @Input('totalRes') totalRes: number;
    @Input('contextMode') contextMode: boolean;
    @Input('currentBasketInfo') currentBasketInfo: any;

    constructor(public http: HttpClient, private notify: NotificationService, public dialog: MatDialog, private router: Router) { }

    ngOnInit(): void { }

    open(x: number, y: number, row: any) {

        this.loadActionList();
        // Adjust the menu anchor position
        this.contextMenuPosition.x = x + 'px';
        this.contextMenuPosition.y = y + 'px';

        this.contextMenuTitle = row.alt_identifier;
        this.contextResId = row.res_id;

        // Opens the menu
        this.contextMenu.openMenu();

        // prevents default
        return false;
    }

    launchEvent(action: any) {
        this.arrRes = [];
        this.currentAction = action;

        this.arrRes = this.selectedRes;


        if (this.contextMode && this.selectedRes.length > 1) {
            this.contextMenuTitle = '';
            this.contextResId = 0;
        }

        if (action.component == 'v1Action' && this.arrRes.length > 1) {
            alert(this.lang.actionMassForbidden);
        } else {
            
            this.http.put('../../rest/resourcesList/users/' + this.currentBasketInfo.ownerId + '/groups/' + this.currentBasketInfo.groupId + '/baskets/' + this.currentBasketInfo.basketId + '/lock', { resources: this.arrRes })
                .subscribe((data: any) => {
                    try {
                        let msgWarn = this.lang.warnLockRes + ' : ' + data.lockers.join(', ');

                        if (data.lockedResources != this.arrRes.length) {
                            msgWarn += this.lang.warnLockRes2 + '.';
                        }

                        if (data.lockedResources > 0) {
                            alert(data.lockedResources + ' ' + msgWarn);
                        }

                        if (data.lockedResources != this.arrRes.length) {
                            this.lock();
                            this[action.component]();
                        }
                    }
                    catch (error) {
                        console.log(error);
                        console.log(action.component);
                        alert(this.lang.actionNotExist);
                    }
                    this.loading = false;
                }, (err: any) => {
                    this.notify.handleErrors(err);
                });
        }

    }

    /* OPEN SPECIFIC ACTION */
    confirmAction() {
        const dialogRef = this.dialog.open(ConfirmActionComponent, {
            width: '500px',
            data: {
                contextMode: this.contextMode,
                contextChrono: this.contextMenuTitle,
                selectedRes: this.selectedRes,
                action: this.currentAction,
                currentBasketInfo: this.currentBasketInfo
            }
        });
        dialogRef.afterClosed().subscribe(result => {
            this.unlock();

            if (result == 'success') {
                this.endAction();
            } else {
                this.unlockRest();
            }
        });
    }

    closeMailAction() {
        const dialogRef = this.dialog.open(CloseMailActionComponent, {
            width: '500px',
            data: {
                contextMode: this.contextMode,
                contextChrono: this.contextMenuTitle,
                selectedRes: this.selectedRes,
                action: this.currentAction,
                currentBasketInfo: this.currentBasketInfo
            }
        });
        dialogRef.afterClosed().subscribe(result => {
            this.unlock();

            if (result == 'success') {
                this.endAction();
            } else {
                this.unlockRest();
            }
        });
    }

    closeAndIndexAction() {
        const dialogRef = this.dialog.open(CloseAndIndexActionComponent, {
            width: '500px',
            data: {
                contextMode: this.contextMode,
                contextChrono: this.contextMenuTitle,
                selectedRes: this.selectedRes,
                action: this.currentAction,
                currentBasketInfo: this.currentBasketInfo
            }
        });
        dialogRef.afterClosed().subscribe(result => {
            this.unlock();

            if (result == 'success') {
                this.endAction();
            } else {
                this.unlockRest();
            }
        });
    }

    updateAcknowledgementSendDateAction() {
        const dialogRef = this.dialog.open(UpdateAcknowledgementSendDateActionComponent, {
            width: '500px',
            data: {
                contextMode: this.contextMode,
                contextChrono: this.contextMenuTitle,
                selectedRes: this.selectedRes,
                action: this.currentAction,
                currentBasketInfo: this.currentBasketInfo
            }
        });
        dialogRef.afterClosed().subscribe(result => {
            this.unlock();

            if (result == 'success') {
                this.endAction();
            } else {
                this.unlockRest();
            }
        });
    }

    createAcknowledgementReceiptsAction() {
        const dialogRef = this.dialog.open(CreateAcknowledgementReceiptActionComponent, {
            width: '600px',
            data: {
                contextMode: this.contextMode,
                contextChrono: this.contextMenuTitle,
                selectedRes: this.selectedRes,
                action: this.currentAction,
                currentBasketInfo: this.currentBasketInfo
            }
        });
        dialogRef.afterClosed().subscribe(result => {
            this.unlock();

            if (result == 'success') {
                this.endAction();
            } else {
                this.unlockRest();
            }
        });
    }

    updateDepartureDateAction() {
        const dialogRef = this.dialog.open(UpdateDepartureDateActionComponent, {
            width: '500px',
            data: {
                contextMode: this.contextMode,
                contextChrono: this.contextMenuTitle,
                selectedRes: this.selectedRes,
                action: this.currentAction,
                currentBasketInfo: this.currentBasketInfo
            }
        });
        dialogRef.afterClosed().subscribe(result => {
            this.unlock();

            if (result == 'success') {
                this.endAction();
            } else {
                this.unlockRest();
            }
        });
    }

    disabledBasketPersistenceAction() {
        const dialogRef = this.dialog.open(DisabledBasketPersistenceActionComponent, {
            width: '500px',
            data: {
                contextMode: this.contextMode,
                contextChrono: this.contextMenuTitle,
                selectedRes: this.selectedRes,
                action: this.currentAction,
                currentBasketInfo: this.currentBasketInfo
            }
        });
        dialogRef.afterClosed().subscribe(result => {
            this.unlock();

            if (result == 'success') {
                this.endAction();
            } else {
                this.unlockRest();
            }
        });
    }

    enabledBasketPersistenceAction() {
        const dialogRef = this.dialog.open(EnabledBasketPersistenceActionComponent, {
            width: '500px',
            data: {
                contextMode: this.contextMode,
                contextChrono: this.contextMenuTitle,
                selectedRes: this.selectedRes,
                action: this.currentAction,
                currentBasketInfo: this.currentBasketInfo
            }
        });
        dialogRef.afterClosed().subscribe(result => {
            this.unlock();

            if (result == 'success') {
                this.endAction();
            } else {
                this.unlockRest();
            }
        });
    }

    resMarkAsReadAction() {
        const dialogRef = this.dialog.open(ResMarkAsReadActionComponent, {
            width: '500px',
            data: {
                contextMode: this.contextMode,
                contextChrono: this.contextMenuTitle,
                selectedRes: this.selectedRes,
                action: this.currentAction,
                currentBasketInfo: this.currentBasketInfo
            }
        });
        dialogRef.afterClosed().subscribe(result => {
            this.unlock();

            if (result == 'success') {
                this.endAction();
            } else {
                this.unlockRest();
            }
        });
    }

    viewDoc() {
        this.dialog.open(ViewDocActionComponent, {
            panelClass: 'no-padding-full-dialog',
            data: {
                contextMode: this.contextMode,
                contextChrono: this.contextMenuTitle,
                selectedRes: this.selectedRes,
                action: this.currentAction,
                currentBasketInfo: this.currentBasketInfo
            }
        });
    }

    sendExternalSignatoryBookAction() {
        const dialogRef = this.dialog.open(SendExternalSignatoryBookActionComponent, {
            width: '500px',
            data: {
                contextMode: this.contextMode,
                contextChrono: this.contextMenuTitle,
                selectedRes: this.selectedRes,
                action: this.currentAction,
                currentBasketInfo: this.currentBasketInfo
            }
        });
        dialogRef.afterClosed().subscribe(result => {
            this.unlock();

            if (result == 'success') {
                this.endAction();
            } else {
                this.unlockRest();
            }
        });
    }

    sendExternalNoteBookAction() {
        const dialogRef = this.dialog.open(SendExternalNoteBookActionComponent, {
            width: '500px',
            data: {
                contextMode: this.contextMode,
                contextChrono: this.contextMenuTitle,
                selectedRes: this.selectedRes,
                action: this.currentAction,
                currentBasketInfo: this.currentBasketInfo
            }
        });
        dialogRef.afterClosed().subscribe(result => {
            this.unlock();

            if (result == 'success') {
                this.endAction();
            } else {
                this.unlockRest();
            }
        });
    }

    redirectAction() {
        const dialogRef = this.dialog.open(RedirectActionComponent, {
            data: {
                contextMode: this.contextMode,
                contextChrono: this.contextMenuTitle,
                selectedRes: this.selectedRes,
                action: this.currentAction,
                currentBasketInfo: this.currentBasketInfo
            }
        });
        dialogRef.afterClosed().subscribe(result => {
            this.unlock();

            if (result == 'success') {
                this.endAction();
            } else {
                this.unlockRest();
            }
        });
    }

    sendShippingAction() {
        const dialogRef = this.dialog.open(SendShippingActionComponent, {
            data: {
                contextMode: this.contextMode,
                contextChrono: this.contextMenuTitle,
                selectedRes: this.selectedRes,
                action: this.currentAction,
                currentBasketInfo: this.currentBasketInfo
            }
        });
        dialogRef.afterClosed().subscribe(result => {
            this.unlock();

            if (result == 'success') {
                this.endAction();
            } else {
                this.unlockRest();
            }
        });
    }

    // CALL GENERIC ACTION V1
    v1Action() {
        location.hash = "";
        window.location.href = 'index.php?page=view_baskets&module=basket&baskets=' + this.currentBasketInfo.basket_id + '&basketId=' + this.currentBasketInfo.basketId + '&resId=' + this.arrRes[0] + '&userId=' + this.currentBasketInfo.ownerId + '&groupIdSer=' + this.currentBasketInfo.groupId + '&defaultAction=' + this.currentAction.id;
        // WHEN V2
        /*this.dialog.open(ProcessActionComponent, {
            width: '500px',
            data: {
                contextMode: this.contextMode,
                contextChrono: this.contextMenuTitle,
                selectedRes: this.selectedRes,
                action: this.currentAction,
                currentBasketInfo: this.currentBasketInfo
            }
        });*/
    }

    // CALL SIGNATUREBOOK WITH V1 METHOD
    signatureBookAction() {
        location.hash = "";
        window.location.href = 'index.php?page=view_baskets&module=basket&baskets=' + this.currentBasketInfo.basket_id + '&basketId=' + this.currentBasketInfo.basketId + '&resId=' + this.arrRes[0] + '&userId=' + this.currentBasketInfo.ownerId + '&groupIdSer=' + this.currentBasketInfo.groupId + '&defaultAction=' + this.currentAction.id + '&signatureBookMode=true';

    }
    ////

    endAction() {
        this.triggerEvent.emit();
        this.notify.success(this.lang.action + ' : "' + this.currentAction.label_action + '" ' + this.lang.done);
    }

    loadActionList() {

        if (JSON.stringify(this.basketInfo) != JSON.stringify(this.currentBasketInfo)) {

            this.basketInfo = JSON.parse(JSON.stringify(this.currentBasketInfo));

            this.http.get('../../rest/resourcesList/users/' + this.currentBasketInfo.ownerId + '/groups/' + this.currentBasketInfo.groupId + '/baskets/' + this.currentBasketInfo.basketId + '/actions')
                .subscribe((data: any) => {
                    if (data.actions.length > 0) {
                        this.actionsList = data.actions;
                    } else {
                        this.actionsList = [{
                            id: 0,
                            label_action: this.lang.noAction,
                            component: ''
                        }];
                    }
                    this.loading = false;
                }, (err: any) => {
                    this.notify.handleErrors(err);
                });
        }
    }

    lock() {
        this.currentLock = setInterval(() => {
            this.http.put('../../rest/resourcesList/users/' + this.currentBasketInfo.ownerId + '/groups/' + this.currentBasketInfo.groupId + '/baskets/' + this.currentBasketInfo.basketId + '/lock', { resources: this.arrRes })
                .subscribe((data: any) => { }, (err: any) => { });
        }, 50000);
    }

    unlock() {
        clearInterval(this.currentLock);
    }

    unlockRest() {
        this.http.put('../../rest/resourcesList/users/' + this.currentBasketInfo.ownerId + '/groups/' + this.currentBasketInfo.groupId + '/baskets/' + this.currentBasketInfo.basketId + '/unlock', { resources: this.arrRes })
            .subscribe((data: any) => { }, (err: any) => { });
    }
}
