import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../app/translate.component';
import { tap, catchError, filter, finalize, exhaustMap } from 'rxjs/operators';
import { of, Subject, Observable } from 'rxjs';
import { NotificationService } from '../notification.service';
import { ConfirmActionComponent } from './confirm-action/confirm-action.component';
import { MatDialog } from '@angular/material';
import { CloseMailActionComponent } from './close-mail-action/close-mail-action.component';
import { RejectVisaBackToPrevousActionComponent } from './visa-reject-back-to-previous-action/reject-visa-back-to-previous-action.component';
import { ResetVisaActionComponent } from './visa-reset-action/reset-visa-action.component';
import { InterruptVisaActionComponent } from './visa-interrupt-action/interrupt-visa-action.component';
import { CloseAndIndexActionComponent } from './close-and-index-action/close-and-index-action.component';
import { UpdateAcknowledgementSendDateActionComponent } from './update-acknowledgement-send-date-action/update-acknowledgement-send-date-action.component';
import { CreateAcknowledgementReceiptActionComponent } from './create-acknowledgement-receipt-action/create-acknowledgement-receipt-action.component';
import { UpdateDepartureDateActionComponent } from './update-departure-date-action/update-departure-date-action.component';
import { DisabledBasketPersistenceActionComponent } from './disabled-basket-persistence-action/disabled-basket-persistence-action.component';
import { EnabledBasketPersistenceActionComponent } from './enabled-basket-persistence-action/enabled-basket-persistence-action.component';
import { ResMarkAsReadActionComponent } from './res-mark-as-read-action/res-mark-as-read-action.component';
import { ViewDocActionComponent } from './view-doc-action/view-doc-action.component';
import { SendExternalSignatoryBookActionComponent } from './send-external-signatory-book-action/send-external-signatory-book-action.component';
import { SendExternalNoteBookActionComponent } from './send-external-note-book-action/send-external-note-book-action.component';
import { RedirectActionComponent } from './redirect-action/redirect-action.component';
import { SendShippingActionComponent } from './send-shipping-action/send-shipping-action.component';
import { redirectInitiatorEntityActionComponent } from './redirect-initiator-entity-action/redirect-initiator-entity-action.component';
import { closeMailWithAttachmentsOrNotesActionComponent } from './close-mail-with-attachments-or-notes-action/close-mail-with-attachments-or-notes-action.component';
import { Router } from '@angular/router';
import { SendSignatureBookActionComponent } from './visa-send-signature-book-action/send-signature-book-action.component';
import { ContinueVisaCircuitActionComponent } from './visa-continue-circuit-action/continue-visa-circuit-action.component';
import { SendAvisWorkflowComponent } from './avis-workflow-send-action/send-avis-workflow-action.component';
import { ContinueAvisCircuitActionComponent } from './avis-continue-circuit-action/continue-avis-circuit-action.component';
import { SendAvisParallelComponent } from './avis-parallel-send-action/send-avis-parallel-action.component';
import { GiveAvisParallelActionComponent } from './avis-give-parallel-action/give-avis-parallel-action.component';
import { ValidateAvisParallelComponent } from './avis-parallel-validate-action/validate-avis-parallel-action.component';
import { HeaderService } from '../../service/header.service';
import { FunctionsService } from '../../service/functions.service';
import { SendAlfrescoActionComponent } from './send-alfresco-action/send-alfresco-action.component';

@Injectable()
export class ActionsService {

    lang: any = LANG;

    mode: string = 'indexing';

    currentResourceLock: any = null;
    lockMode: boolean = true;

    currentAction: any = null;
    currentUserId: number = null;
    currentGroupId: number = null;
    currentBasketId: number = null;
    currentResIds: number[] = [];
    currentResourceInformations: any = null;

    loading: boolean = false;

    indexActionRoute: string;
    processActionRoute: string;

    private eventAction = new Subject<any>();

    constructor(
        public http: HttpClient,
        public dialog: MatDialog,
        private notify: NotificationService,
        private router: Router,
        public headerService: HeaderService,
        private functions: FunctionsService
    ) {
    }

    catchAction(): Observable<any> {
        return this.eventAction.asObservable();
    }

    setLoading(state: boolean) {
        this.loading = state;
    }

    setActionInformations(action: any, userId: number, groupId: number, basketId: number, resIds: number[]) {
        if (action !== null && action.component === null) {
            return false;
        } else if (action !== null && userId > 0 && groupId > 0) {
            this.mode = basketId === null ? 'indexing' : 'process';
            this.currentAction = action;
            this.currentUserId = userId;
            this.currentGroupId = groupId;
            this.currentBasketId = basketId;
            this.currentResIds = resIds === null ? [] : resIds;

            this.indexActionRoute = `../../rest/indexing/groups/${this.currentGroupId}/actions/${this.currentAction.id}`;
            this.processActionRoute = `../../rest/resourcesList/users/${this.currentUserId}/groups/${this.currentGroupId}/baskets/${this.currentBasketId}/actions/${this.currentAction.id}`;

            return true;
        } else {
            console.log('Bad informations: ');
            console.log({ 'action': action }, { 'userId': userId }, { 'groupId': groupId }, { 'basketId': basketId }, { 'resIds': resIds });

            this.notify.error('Une erreur est survenue');
            return false;
        }
    }

    saveDocument(datas: any) {
        this.loading = true;
        this.setResourceInformations(datas);
        return this.http.post('../../rest/resources', this.currentResourceInformations);
    }

    setResourceInformations(datas: any) {
        this.currentResourceInformations = datas;
    }

    setResourceIds(resId: number[]) {
        this.currentResourceInformations['resId'] = resId;
        this.currentResIds = resId;
    }

    launchIndexingAction(action: any, userId: number, groupId: number, datas: any) {

        if (this.setActionInformations(action, userId, groupId, null, null)) {
            this.setResourceInformations(datas);

            this.loading = true;
            try {
                this[action.component]();
            }
            catch (error) {
                console.log(error);
                console.log(action.component);
                alert(this.lang.actionNotExist);
            }
        }
    }

    launchAction(action: any, userId: number, groupId: number, basketId: number, resIds: number[], datas: any, lockRes: boolean = true) {
        if (this.setActionInformations(action, userId, groupId, basketId, resIds)) {
            this.loading = true;
            this.lockMode = lockRes;
            this.setResourceInformations(datas);
            if (this.lockMode) {
                if (action.component == 'viewDoc') {
                    this[action.component](action.data);
                } else {
                    this.http.put(`../../rest/resourcesList/users/${userId}/groups/${groupId}/baskets/${basketId}/lock`, { resources: resIds }).pipe(
                        tap((data: any) => {
                            if (this.canExecuteAction(data.lockedResources, data.lockers, resIds)) {
                                try {
                                    this.lockResource();
                                    this[action.component](action.data);
                                }
                                catch (error) {
                                    console.log(error);
                                    console.log(action);
                                    alert(this.lang.actionNotExist);
                                }
                            }
                        }),
                        catchError((err: any) => {
                            this.notify.handleErrors(err);
                            return of(false);
                        })
                    ).subscribe();
                }
            } else {
                try {
                    this[action.component]();
                }
                catch (error) {
                    console.log(error);
                    console.log(action);
                    alert(this.lang.actionNotExist);
                }
            }
        }
    }

    canExecuteAction(numberOflockedResIds: number, usersWholocked: any[], resIds: number[]) {
        let msgWarn = this.lang.warnLockRes + ' : ' + usersWholocked.join(', ');

        if (numberOflockedResIds != resIds.length) {
            msgWarn += this.lang.warnLockRes2 + '.';
        }

        if (numberOflockedResIds > 0) {
            alert(numberOflockedResIds + ' ' + msgWarn);
        }

        if (numberOflockedResIds != resIds.length) {
            return true;
        } else {
            return false;
        }
    }

    lockResource() {
        this.currentResourceLock = setInterval(() => {
            this.http.put(`../../rest/resourcesList/users/${this.currentUserId}/groups/${this.currentGroupId}/baskets/${this.currentBasketId}/lock`, { resources: this.currentResIds }).pipe(
                catchError((err: any) => {
                    if (err.status == 403) {
                        clearInterval(this.currentResourceLock);
                    }
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        }, 50000);
    }

    unlockResource() {
        if (this.currentResIds.length > 0) {
            this.http.put(`../../rest/resourcesList/users/${this.currentUserId}/groups/${this.currentGroupId}/baskets/${this.currentBasketId}/unlock`, { resources: this.currentResIds }).pipe(
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        }
    }

    stopRefreshResourceLock() {
        if (this.currentResourceLock !== null) {
            clearInterval(this.currentResourceLock);
        }
    }

    setDatasActionToSend() {
        return {
            resIds: this.currentResIds,
            resource: this.currentResourceInformations,
            action: this.currentAction,
            userId: this.currentUserId,
            groupId: this.currentGroupId,
            basketId: this.currentBasketId,
            indexActionRoute: this.indexActionRoute,
            processActionRoute: this.processActionRoute
        }
    }

    unlockResourceAfterActionModal(resIds: any) {
        this.stopRefreshResourceLock();
        if (this.functions.empty(resIds) && this.lockMode) {
            this.unlockResource();
        }
    }

    endAction(resIds: any) {
        this.notify.success(this.lang.action + ' : "' + this.currentAction.label + '" ' + this.lang.done);

        this.eventAction.next(resIds);
    }

    /* OPEN SPECIFIC ACTION */
    confirmAction(options: any = null) {

        const dialogRef = this.dialog.open(ConfirmActionComponent, {
            panelClass: 'maarch-modal',
            disableClose: true,
            width: '500px',
            data: this.setDatasActionToSend()
        });

        dialogRef.afterClosed().pipe(
            tap((resIds: any) => {
                this.unlockResourceAfterActionModal(resIds);
            }),
            filter((resIds: any) => !this.functions.empty(resIds)),
            tap((resIds: any) => {
                this.endAction(resIds);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    closeMailAction(options: any = null) {
        const dialogRef = this.dialog.open(CloseMailActionComponent, {
            disableClose: true,
            width: '500px',
            panelClass: 'maarch-modal',
            data: this.setDatasActionToSend()
        });
        dialogRef.afterClosed().pipe(
            tap((resIds: any) => {
                this.unlockResourceAfterActionModal(resIds);
            }),
            filter((resIds: any) => !this.functions.empty(resIds)),
            tap((resIds: any) => {
                this.endAction(resIds);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    closeAndIndexAction(options: any = null) {
        const dialogRef = this.dialog.open(CloseAndIndexActionComponent, {
            panelClass: 'maarch-modal',
            disableClose: true,
            width: '500px',
            data: this.setDatasActionToSend()
        });
        dialogRef.afterClosed().pipe(
            tap((resIds: any) => {
                this.unlockResourceAfterActionModal(resIds);
            }),
            filter((resIds: any) => !this.functions.empty(resIds)),
            tap((resIds: any) => {
                this.endAction(resIds);
                let firstGroup: number = 0;
                let redirectAfterClose = '';
                this.headerService.user.groups.filter((group: any) => group.can_index === true).forEach((group: any) => {
                    if (firstGroup == 0) {
                        firstGroup = group.id;
                    }
                    if (group.id == this.currentGroupId) {
                        redirectAfterClose = '/indexing/' + this.currentGroupId;
                    }
                });
                if (redirectAfterClose != '') {
                    this.router.navigate([redirectAfterClose], { queryParams: { refresh: new Date().getTime() } });
                } else if (firstGroup == 0) {
                    this.router.navigate(['/home']);
                } else {
                    this.router.navigate(['/indexing/' + firstGroup], { queryParams: { refresh: new Date().getTime() } });
                }
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    redirectInitiatorEntityAction(options: any = null) {
        const dialogRef = this.dialog.open(redirectInitiatorEntityActionComponent, {
            panelClass: 'maarch-modal',
            disableClose: true,
            width: '500px',
            data: this.setDatasActionToSend()
        });
        dialogRef.afterClosed().pipe(
            tap((resIds: any) => {
                this.unlockResourceAfterActionModal(resIds);
            }),
            filter((resIds: any) => !this.functions.empty(resIds)),
            tap((resIds: any) => {
                this.endAction(resIds);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    closeMailWithAttachmentsOrNotesAction(options: any = null) {
        const dialogRef = this.dialog.open(closeMailWithAttachmentsOrNotesActionComponent, {
            panelClass: 'maarch-modal',
            disableClose: true,
            width: '500px',
            data: this.setDatasActionToSend()
        });
        dialogRef.afterClosed().pipe(
            tap((resIds: any) => {
                this.unlockResourceAfterActionModal(resIds);
            }),
            filter((resIds: any) => !this.functions.empty(resIds)),
            tap((resIds: any) => {
                this.endAction(resIds);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    updateAcknowledgementSendDateAction(options: any = null) {
        const dialogRef = this.dialog.open(UpdateAcknowledgementSendDateActionComponent, {
            panelClass: 'maarch-modal',
            disableClose: true,
            width: '500px',
            data: this.setDatasActionToSend()
        });
        dialogRef.afterClosed().pipe(
            tap((resIds: any) => {
                this.unlockResourceAfterActionModal(resIds);
            }),
            filter((resIds: any) => !this.functions.empty(resIds)),
            tap((resIds: any) => {
                this.endAction(resIds);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    createAcknowledgementReceiptsAction(options: any = null) {
        const dialogRef = this.dialog.open(CreateAcknowledgementReceiptActionComponent, {
            panelClass: 'maarch-modal',
            disableClose: true,
            width: '600px',
            data: this.setDatasActionToSend()
        });
        dialogRef.afterClosed().pipe(
            tap((resIds: any) => {
                this.unlockResourceAfterActionModal(resIds);
            }),
            filter((resIds: any) => !this.functions.empty(resIds)),
            tap((resIds: any) => {
                this.endAction(resIds);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    updateDepartureDateAction(options: any = null) {
        const dialogRef = this.dialog.open(UpdateDepartureDateActionComponent, {
            panelClass: 'maarch-modal',
            disableClose: true,
            width: '500px',
            data: this.setDatasActionToSend()
        });
        dialogRef.afterClosed().pipe(
            tap((resIds: any) => {
                this.unlockResourceAfterActionModal(resIds);
            }),
            filter((resIds: any) => !this.functions.empty(resIds)),
            tap((resIds: any) => {
                this.endAction(resIds);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    disabledBasketPersistenceAction(options: any = null) {
        const dialogRef = this.dialog.open(DisabledBasketPersistenceActionComponent, {
            disableClose: true,
            width: '500px',
            data: this.setDatasActionToSend()
        });
        dialogRef.afterClosed().pipe(
            tap((resIds: any) => {
                this.unlockResourceAfterActionModal(resIds);
            }),
            filter((resIds: any) => !this.functions.empty(resIds)),
            tap((resIds: any) => {
                this.endAction(resIds);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    enabledBasketPersistenceAction(options: any = null) {
        const dialogRef = this.dialog.open(EnabledBasketPersistenceActionComponent, {
            disableClose: true,
            width: '500px',
            data: this.setDatasActionToSend()
        });
        dialogRef.afterClosed().pipe(
            tap((resIds: any) => {
                this.unlockResourceAfterActionModal(resIds);
            }),
            filter((resIds: any) => !this.functions.empty(resIds)),
            tap((resIds: any) => {
                this.endAction(resIds);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    resMarkAsReadAction(options: any = null) {
        const dialogRef = this.dialog.open(ResMarkAsReadActionComponent, {
            disableClose: true,
            width: '500px',
            data: this.setDatasActionToSend()
        });
        dialogRef.afterClosed().pipe(
            tap((resIds: any) => {
                this.unlockResourceAfterActionModal(resIds);
            }),
            filter((resIds: any) => !this.functions.empty(resIds)),
            tap((resIds: any) => {
                this.endAction(resIds);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    viewDoc(options: any = null) {
        this.dialog.open(ViewDocActionComponent, {
            panelClass: 'maarch-modal',
            data: this.setDatasActionToSend()
        });
    }

    sendExternalSignatoryBookAction(options: any = null) {
        const dialogRef = this.dialog.open(SendExternalSignatoryBookActionComponent, {
            panelClass: 'maarch-modal',
            disableClose: true,
            width: '500px',
            data: this.setDatasActionToSend()
        });
        dialogRef.afterClosed().pipe(
            tap((resIds: any) => {
                this.unlockResourceAfterActionModal(resIds);
            }),
            filter((resIds: any) => !this.functions.empty(resIds)),
            tap((resIds: any) => {
                this.endAction(resIds);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    sendExternalNoteBookAction(options: any = null) {
        const dialogRef = this.dialog.open(SendExternalNoteBookActionComponent, {
            panelClass: 'maarch-modal',
            disableClose: true,
            width: '500px',
            data: this.setDatasActionToSend()
        });
        dialogRef.afterClosed().pipe(
            tap((resIds: any) => {
                this.unlockResourceAfterActionModal(resIds);
            }),
            filter((resIds: any) => !this.functions.empty(resIds)),
            tap((resIds: any) => {
                this.endAction(resIds);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    redirectAction(options: any = null) {
        const dialogRef = this.dialog.open(RedirectActionComponent, {
            panelClass: 'maarch-modal',
            disableClose: true,
            data: this.setDatasActionToSend()
        });
        dialogRef.afterClosed().pipe(
            tap((resIds: any) => {
                this.unlockResourceAfterActionModal(resIds);
            }),
            filter((resIds: any) => !this.functions.empty(resIds)),
            tap((resIds: any) => {
                this.endAction(resIds);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    sendShippingAction(options: any = null) {
        const dialogRef = this.dialog.open(SendShippingActionComponent, {
            panelClass: 'maarch-modal',
            disableClose: true,
            minWidth: '500px',
            data: this.setDatasActionToSend()
        });
        dialogRef.afterClosed().pipe(
            tap((resIds: any) => {
                this.unlockResourceAfterActionModal(resIds);
            }),
            filter((resIds: any) => !this.functions.empty(resIds)),
            tap((resIds: any) => {
                this.endAction(resIds);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    sendSignatureBookAction(options: any = null) {
        const dialogRef = this.dialog.open(SendSignatureBookActionComponent, {
            panelClass: 'maarch-modal',
            autoFocus: false,
            disableClose: true,
            data: this.setDatasActionToSend()
        });
        dialogRef.afterClosed().pipe(
            tap((resIds: any) => {
                this.unlockResourceAfterActionModal(resIds);
            }),
            filter((resIds: any) => !this.functions.empty(resIds)),
            tap((resIds: any) => {
                this.endAction(resIds);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    continueVisaCircuitAction(options: any = null) {
        const dialogRef = this.dialog.open(ContinueVisaCircuitActionComponent, {
            panelClass: 'maarch-modal',
            autoFocus: false,
            disableClose: true,
            data: this.setDatasActionToSend()
        });
        dialogRef.afterClosed().pipe(
            tap((resIds: any) => {
                this.unlockResourceAfterActionModal(resIds);
            }),
            filter((resIds: any) => !this.functions.empty(resIds)),
            tap((resIds: any) => {
                this.endAction(resIds);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }


    noConfirmAction(options: any = null) {
        let dataActionToSend = this.setDatasActionToSend();
        if (dataActionToSend.resIds.length === 0) {
            this.http.post('../../rest/resources', dataActionToSend.resource).pipe(
                tap((data: any) => {
                    dataActionToSend.resIds = [data.resId];
                }),
                exhaustMap(() => this.http.put(dataActionToSend.indexActionRoute, {
                    resource: dataActionToSend.resIds[0]
                })),
                tap(() => {
                    if (!this.functions.empty(dataActionToSend.resource['followed']) && dataActionToSend.resource['followed']) {
                        this.headerService.nbResourcesFollowed++;
                    }
                    this.endAction(dataActionToSend.resIds);
                }),
                finalize(() => this.loading = false),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        } else {
            this.http.put(dataActionToSend.processActionRoute, { resources: this.setDatasActionToSend().resIds }).pipe(
                tap((resIds: any) => {
                    this.endAction(resIds);
                }),
                finalize(() => this.loading = false),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        }
    }

    processDocument(options: any = null) {
        this.stopRefreshResourceLock();
        this.unlockResource();
        this.router.navigate([`/process/users/${this.currentUserId}/groups/${this.currentGroupId}/baskets/${this.currentBasketId}/resId/${this.currentResIds}`]);
    }

    signatureBookAction(options: any = null) {
        this.router.navigate([`/signatureBook/users/${this.currentUserId}/groups/${this.currentGroupId}/baskets/${this.currentBasketId}/resources/${this.currentResIds}`]);
    }

    documentDetails(options: any = null) {
        this.router.navigate([`/resources/${this.currentResIds}`]);
    }

    rejectVisaBackToPreviousAction(options: any = null) {
        const dialogRef = this.dialog.open(RejectVisaBackToPrevousActionComponent, {
            panelClass: 'maarch-modal',
            disableClose: true,
            width: '500px',
            data: this.setDatasActionToSend()
        });
        dialogRef.afterClosed().pipe(
            tap((resIds: any) => {
                this.unlockResourceAfterActionModal(resIds);
            }),
            filter((resIds: any) => !this.functions.empty(resIds)),
            tap((resIds: any) => {
                this.endAction(resIds);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    resetVisaAction(options: any = null) {
        const dialogRef = this.dialog.open(ResetVisaActionComponent, {
            panelClass: 'maarch-modal',
            disableClose: true,
            width: '500px',
            data: this.setDatasActionToSend()
        });
        dialogRef.afterClosed().pipe(
            tap((resIds: any) => {
                this.unlockResourceAfterActionModal(resIds);
            }),
            filter((resIds: any) => !this.functions.empty(resIds)),
            tap((resIds: any) => {
                this.endAction(resIds);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    interruptVisaAction(options: any = null) {
        const dialogRef = this.dialog.open(InterruptVisaActionComponent, {
            panelClass: 'maarch-modal',
            disableClose: true,
            width: '500px',
            data: this.setDatasActionToSend()
        });
        dialogRef.afterClosed().pipe(
            tap((resIds: any) => {
                this.unlockResourceAfterActionModal(resIds);
            }),
            filter((resIds: any) => !this.functions.empty(resIds)),
            tap((resIds: any) => {
                this.endAction(resIds);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    sendToOpinionCircuitAction(options: any = null) {
        const dialogRef = this.dialog.open(SendAvisWorkflowComponent, {
            panelClass: 'maarch-modal',
            autoFocus: false,
            disableClose: true,
            data: this.setDatasActionToSend()
        });
        dialogRef.afterClosed().pipe(
            tap((resIds: any) => {
                this.unlockResourceAfterActionModal(resIds);
            }),
            filter((resIds: any) => !this.functions.empty(resIds)),
            tap((resIds: any) => {
                this.endAction(resIds);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    sendToParallelOpinion(options: any = null) {
        const dialogRef = this.dialog.open(SendAvisParallelComponent, {
            panelClass: 'maarch-modal',
            autoFocus: false,
            disableClose: true,
            data: this.setDatasActionToSend()
        });
        dialogRef.afterClosed().pipe(
            tap((resIds: any) => {
                this.unlockResourceAfterActionModal(resIds);
            }),
            filter((resIds: any) => !this.functions.empty(resIds)),
            tap((resIds: any) => {
                this.endAction(resIds);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    continueOpinionCircuitAction(options: any = null) {
        const dialogRef = this.dialog.open(ContinueAvisCircuitActionComponent, {
            panelClass: 'maarch-modal',
            autoFocus: false,
            disableClose: true,
            data: this.setDatasActionToSend()
        });
        dialogRef.afterClosed().pipe(
            tap((resIds: any) => {
                this.unlockResourceAfterActionModal(resIds);
            }),
            filter((resIds: any) => !this.functions.empty(resIds)),
            tap((resIds: any) => {
                this.endAction(resIds);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    giveOpinionParallelAction(options: any = null) {
        const dialogRef = this.dialog.open(GiveAvisParallelActionComponent, {
            panelClass: 'maarch-modal',
            autoFocus: false,
            disableClose: true,
            data: this.setDatasActionToSend()
        });
        dialogRef.afterClosed().pipe(
            tap((resIds: any) => {
                this.unlockResourceAfterActionModal(resIds);
            }),
            filter((resIds: any) => !this.functions.empty(resIds)),
            tap((resIds: any) => {
                this.endAction(resIds);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    validateParallelOpinionDiffusionAction(options: any = null) {
        const dialogRef = this.dialog.open(ValidateAvisParallelComponent, {
            panelClass: 'maarch-modal',
            autoFocus: false,
            disableClose: true,
            data: this.setDatasActionToSend()
        });
        dialogRef.afterClosed().pipe(
            tap((resIds: any) => {
                this.unlockResourceAfterActionModal(resIds);
            }),
            filter((resIds: any) => !this.functions.empty(resIds)),
            tap((resIds: any) => {
                this.endAction(resIds);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    sendAlfrescoAction(options: any = null) {
        const dialogRef = this.dialog.open(SendAlfrescoActionComponent, {
            autoFocus: false,
            disableClose: true,
            data: this.setDatasActionToSend()
        });
        dialogRef.afterClosed().pipe(
            tap((data: any) => {
                this.unlockResourceAfterActionModal(data);
            }),
            filter((data: string) => data === 'success'),
            tap((result: any) => {
                this.endAction(result);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }
}
