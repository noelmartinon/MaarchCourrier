import { Component, OnInit, NgZone, ViewChild, OnDestroy } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router, ActivatedRoute } from '@angular/router';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import { tap, catchError, filter } from 'rxjs/operators';
import { PrivilegeService } from '@service/privileges.service';
import { MatDialogRef, MatDialog } from '@angular/material/dialog';
import { AttachmentCreateComponent } from './attachments/attachment-create/attachment-create.component';
import { FunctionsService } from '@service/functions.service';
import { AttachmentPageComponent } from './attachments/attachments-page/attachment-page.component';
import { VisaWorkflowComponent } from './visa/visa-workflow.component';
import { ActionsService } from './actions/actions.service';
import { HeaderService } from '@service/header.service';
import { AppService } from '@service/app.service';
import { of, Subscription } from 'rxjs';
import { DocumentViewerComponent } from './viewer/document-viewer.component';
import { ConfirmComponent } from '@plugins/modal/confirm.component';
import { NotesListComponent } from './notes/notes-list.component';

declare var $: any;

@Component({
    templateUrl: 'signature-book.component.html',
    styleUrls: ['signature-book.component.scss'],
})
export class SignatureBookComponent implements OnInit, OnDestroy {

    resId: number;
    basketId: number;
    groupId: number;
    userId: number;

    signatureBook: any = {
        consigne: '',
        documents: [],
        attachments: [],
        resList: [],
        resListIndex: 0,
        lang: {}
    };

    rightSelectedThumbnail: number = 0;
    leftSelectedThumbnail: number = 0;
    rightViewerLink: string = '';
    leftViewerLink: string = '';
    headerTab: string = 'document';
    showTopRightPanel: boolean = false;
    showTopLeftPanel: boolean = false;
    showResLeftPanel: boolean = true;
    showLeftPanel: boolean = true;
    showRightPanel: boolean = true;
    showAttachmentPanel: boolean = false;
    showSignaturesPanel: boolean = false;
    loading: boolean = false;
    loadingSign: boolean = false;
    canUpdateDocument: boolean = false;
    // SGAMI
    leftDocumentDisplay : boolean = false;
    letIconDisplay: boolean = false;
    // END
    //SGAMI-SO #75
    basket: any = null
    //SGAMI-SO #75

    subscription: Subscription;
    currentResourceLock: any = null;

    leftContentWidth: string = '44%';
    rightContentWidth: string = '44%';
    dialogRef: MatDialogRef<any>;

    processTool: any[] = [
        {
            id: 'notes',
            icon: 'fas fa-pen-square fa-2x',
            label: this.translate.instant('lang.notesAlt'),
            count: 0
        },
        {
            id: 'visaCircuit',
            icon: 'fas fa-list-ol fa-2x',
            label: this.translate.instant('lang.visaWorkflow'),
            count: 0
        },
        {
            id: 'history',
            icon: 'fas fa-history fa-2x',
            label: this.translate.instant('lang.history'),
            count: 0
        },
        {
            id: 'linkedResources',
            icon: 'fas fa-link fa-2x',
            label: this.translate.instant('lang.links'),
            count: 0
        }
    ];

    @ViewChild('appVisaWorkflow', { static: false }) appVisaWorkflow: VisaWorkflowComponent;
    @ViewChild('appDocumentViewer', { static: false }) appDocumentViewer: DocumentViewerComponent;
    // SGAMI
    @ViewChild('appDocumentViewerLeft', { static: false }) appDocumentViewerLeft: DocumentViewerComponent;
    // END
    @ViewChild('appNotesList', { static: false }) appNotesList: NotesListComponent;

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        private appService: AppService,
        private route: ActivatedRoute,
        private router: Router,
        private zone: NgZone,
        private notify: NotificationService,
        public privilegeService: PrivilegeService,
        public dialog: MatDialog,
        public functions: FunctionsService,
        public actionService: ActionsService,
        public headerService: HeaderService,
    ) {
        (<any>window).pdfWorkerSrc = 'pdfjs/pdf.worker.min.js';

        // Event after process action
        this.subscription = this.actionService.catchAction().subscribe(message => {
            this.processAfterAction();
        });
    }

    ngOnInit(): void {        
        this.loading = true;

        this.route.params.subscribe(params => {
            this.resId = +params['resId'];
            this.basketId = params['basketId'];
            this.groupId = params['groupId'];
            this.userId = params['userId'];

            this.signatureBook.resList = []; // This line is added because of manage action behaviour (processAfterAction is called twice)

            this.actionService.lockResource(this.userId, this.groupId, this.basketId, [this.resId]);

            this.http.get('../rest/signatureBook/users/' + this.userId + '/groups/' + this.groupId + '/baskets/' + this.basketId + '/resources/' + this.resId)
                .subscribe((data: any) => {
                    if (data.error) {
                        location.hash = '';
                        location.search = '';
                        return;
                    }
                    this.signatureBook = data;
                    console.dir("this.signatureBook", this.signatureBook);
                    this.canUpdateDocument = data.canUpdateDocuments;
                    this.headerTab = 'document';
                    this.leftSelectedThumbnail = 0;
                    this.rightSelectedThumbnail = 0;
                    this.leftViewerLink = '';
                    this.rightViewerLink = '';
                    this.showLeftPanel = true;
                    this.showRightPanel = true;
                    this.showResLeftPanel = true;
                    this.showTopLeftPanel = false;
                    this.showTopRightPanel = false;
                    this.showAttachmentPanel = false;

                    this.leftContentWidth = '44%';
                    this.rightContentWidth = '44%';

                    // SGAMI             
                    if (this.signatureBook.attachments[1]) {
                        this.rightSelectedThumbnail = 1;
                        this.rightViewerLink = this.signatureBook.attachments[1].viewerLink;
                        this.leftDocumentDisplay = true;
                        this.letIconDisplay = true;
                    }

                    if (this.signatureBook.documents[0]) {
                        this.leftViewerLink = this.signatureBook.attachments[0].viewerLink;
                        if (this.signatureBook.documents[0].inSignatureBook && this.leftDocumentDisplay == false) {                            
                            this.headerTab = 'visaCircuit';
                        }
                    }
                    // END

                    this.signatureBook.resListIndex = this.signatureBook.resList.map((e: any) => e.res_id).indexOf(this.resId);

                    this.displayPanel('RESLEFT');
                    this.loading = false;

                    setTimeout(() => {
                        $('#rightPanelContent').niceScroll({ touchbehavior: false, cursorcolor: '#666', cursoropacitymax: 0.6, cursorwidth: '4' });

                        if ($('.tooltipstered').length === 0) {
                            $('#obsVersion').tooltipster({
                                theme: 'tooltipster-light',
                                interactive: true
                            });
                        }
                    }, 0);
                    this.loadBadges();
                    this.loadActions();
                    
                    if (this.appDocumentViewer !== undefined) {
                        this.appDocumentViewer.loadRessource(this.signatureBook.attachments[this.rightSelectedThumbnail].signed ? this.signatureBook.attachments[this.rightSelectedThumbnail].viewerId : this.signatureBook.attachments[this.rightSelectedThumbnail].res_id, this.signatureBook.attachments[this.rightSelectedThumbnail].isResource ?  'mainDocument' : 'attachment');
                    }
                }, (err) => {
                    this.notify.error(err.error.errors);
                    setTimeout(() => {
                        this.backToBasket();
                    }, 2000);

                });
        });
    }

    loadActions() {      
        this.http.get('../rest/resourcesList/users/' + this.userId + '/groups/' + this.groupId + '/baskets/' + this.basketId + '/actions?resId=' + this.resId)
            .subscribe((data: any) => {
                this.signatureBook.actions = data.actions                
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    processAfterAction() {
        let idToGo = -1;
        const c = this.signatureBook.resList.length;

        for (let i = 0; i < c; i++) {
            if (this.signatureBook.resList[i].res_id === this.resId) {
                if (this.signatureBook.resList[i + 1]) {
                    idToGo = this.signatureBook.resList[i + 1].res_id;
                } else if (i > 0) {
                    idToGo = this.signatureBook.resList[i - 1].res_id;
                }
            }
        }

        if (c > 0) { // This (if)line is added because of manage action behaviour (processAfterAction is called twice)
            if (idToGo >= 0) {
                $('#send').removeAttr('disabled');
                $('#send').css('opacity', '1');

                this.changeLocation(idToGo, 'action');
            } else {
                this.backToBasket();
            }
        }
    }

    changeSignatureBookLeftContent(id: string) {
        // SGAMI
        if(id === 'document') {
            this.leftDocumentDisplay = true;
            if(this.rightSelectedThumbnail == 0){
                this.rightSelectedThumbnail = 1;
                this.appDocumentViewer.loadRessource(this.signatureBook.attachments[this.rightSelectedThumbnail].signed ? this.signatureBook.attachments[this.rightSelectedThumbnail].viewerId : this.signatureBook.attachments[this.rightSelectedThumbnail].res_id, 'attachment');
            }
        } 
        else if(id !== 'document' && this.letIconDisplay == true ) {
            this.leftDocumentDisplay = false;
        } 
        //  END SGAMI

        if (this.isToolModified()) {            
            const dialogRef = this.openConfirmModification();

            dialogRef.afterClosed().pipe(
                tap((data: string) => {
                    if (data !== 'ok') {
                        this.headerTab = id;
                        this.showTopLeftPanel = false;
                    }
                }),
                filter((data: string) => data === 'ok'),
                tap(() => {
                    this.saveTool();
                    this.headerTab = id;
                    this.showTopLeftPanel = false;
                }),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        } else {
            this.headerTab = id;
            this.showTopLeftPanel = false;
        }
    }

    isToolModified() {
        if (this.headerTab === 'visaCircuit' && this.appVisaWorkflow !== undefined && this.appVisaWorkflow.isModified()) {
            return true;
        } else if (this.headerTab === 'notes' && this.appNotesList !== undefined && this.appNotesList.isModified()) {
            return true;
        } else {
            return false;
        }
    }

    async saveTool() {
        if (this.headerTab === 'visaCircuit' && this.appVisaWorkflow !== undefined) {
            await this.appVisaWorkflow.saveVisaWorkflow();
            this.loadBadges();
        } else if (this.headerTab === 'notes' && this.appNotesList !== undefined) {
            this.appNotesList.addNote();
            this.loadBadges();
        }
    }

    openConfirmModification() {
        return this.dialog.open(ConfirmComponent, { panelClass: 'maarch-modal', autoFocus: false, disableClose: true, data: { title: this.translate.instant('lang.confirm'), msg: this.translate.instant('lang.saveModifiedData'), buttonValidate: this.translate.instant('lang.yes'), buttonCancel: this.translate.instant('lang.no') } });
    }

    changeRightViewer(index: number) {
        this.showAttachmentPanel = false;
        if (this.signatureBook.attachments[index]) {
            this.rightViewerLink = this.signatureBook.attachments[index].viewerLink;
        } else {
            this.rightViewerLink = '';
        }
        this.rightSelectedThumbnail = index;
        // SGAMI
        this.appDocumentViewer.loadRessource(this.signatureBook.attachments[this.rightSelectedThumbnail].signed ? this.signatureBook.attachments[this.rightSelectedThumbnail].viewerId : this.signatureBook.attachments[this.rightSelectedThumbnail].res_id, this.signatureBook.attachments[this.rightSelectedThumbnail].isResource ? 'mainDocument' : 'attachment');
        // END SGAMI
    }

    changeLeftViewer(index: number) {
        this.leftViewerLink = this.signatureBook.documents[index].viewerLink;
        this.leftSelectedThumbnail = index;
    }

    displayPanel(panel: string) {
        if (panel === 'TOPRIGHT') {
            this.showTopRightPanel = !this.showTopRightPanel;
        } else if (panel === 'TOPLEFT') {
            this.showTopLeftPanel = !this.showTopLeftPanel;
        } else if (panel === 'LEFT') {
            this.showLeftPanel = !this.showLeftPanel;
            this.showResLeftPanel = false;
            if (!this.showLeftPanel) {
                this.rightContentWidth = '96%';
                $('#hideLeftContent').css('background', 'none');
            } else {
                this.rightContentWidth = '48%';
                this.leftContentWidth = '48%';
                $('#hideLeftContent').css('background', '#fbfbfb');
            }
        } else if (panel === 'RESLEFT') {
            this.showResLeftPanel = !this.showResLeftPanel;
            if (!this.showResLeftPanel) {
                this.rightContentWidth = '48%';
                this.leftContentWidth = '48%';
            } else {
                this.rightContentWidth = '44%';
                this.leftContentWidth = '44%';
                if (this.signatureBook.resList.length === 0 || typeof this.signatureBook.resList[0].creation_date === 'undefined') {
                    this.http.get('../rest/signatureBook/users/' + this.userId + '/groups/' + this.groupId + '/baskets/' + this.basketId + '/resources')
                        .subscribe((data: any) => {
                            this.signatureBook.resList = data.resources;
                            this.signatureBook.resList.forEach((value: any, index: number) => {
                                if (value.res_id == this.resId) {
                                    this.signatureBook.resListIndex = index;
                                }
                            });
                            setTimeout(() => {
                                $('#resListContent').niceScroll({ touchbehavior: false, cursorcolor: '#666', cursoropacitymax: 0.6, cursorwidth: '4' });
                                $('#resListContent').scrollTop(0);
                                $('#resListContent').scrollTop($('.resListContentFrameSelected').offset().top - 42);
                            }, 0);
                        });
                }
            }
        } else if (panel === 'MIDDLE') {
            this.showRightPanel = !this.showRightPanel;
            this.showResLeftPanel = false;
            if (!this.showRightPanel) {
                this.leftContentWidth = '96%';
                $('#contentLeft').css('border-right', 'none');
            } else {
                this.rightContentWidth = '48%';
                this.leftContentWidth = '48%';
                $('#contentLeft').css('border-right', 'solid 1px');
            }
        }
    }

    displayAttachmentPanel() {
        this.showAttachmentPanel = !this.showAttachmentPanel;
        this.rightSelectedThumbnail = 0;
        if (this.signatureBook.attachments[0]) {
            this.rightViewerLink = this.signatureBook.attachments[0].viewerLink;
        }
    }

    refreshAttachments(mode: string = 'rightContent') {
        if (mode === 'rightContent') {
            this.http.get('../rest/signatureBook/' + this.resId + '/incomingMailAttachments')
                .subscribe((data: any) => {
                    this.signatureBook.documents = data;
                });
        }
        this.http.get('../rest/signatureBook/' + this.resId + '/attachments')
            .subscribe((data: any) => {
                let i = 0;
                if (mode === 'add') {
                    let found = false;
                    data.forEach((elem: any, index: number) => {
                        if (!found && (!this.signatureBook.attachments[index] || elem.res_id != this.signatureBook.attachments[index].res_id)) {
                            i = index;
                            found = true;
                        }
                    });
                } else if (mode === 'edit') {
                    const id = this.signatureBook.attachments[this.rightSelectedThumbnail].res_id;
                    data.forEach((elem: any, index: number) => {
                        if (elem.res_id == id) {
                            i = index;
                        }
                    });
                }

                this.signatureBook.attachments = data;
                if (mode === 'add' || mode === 'edit') {
                    this.changeRightViewer(i);
                // SGAMI 
                    this.leftDocumentDisplay = true;
                    this.letIconDisplay = true;
                    if(this.signatureBook.attachments.length === 2) {
                        this.changeSignatureBookLeftContent('document');
                    }
                } else if (mode === 'del') {
                    if(this.signatureBook.attachments.length >= 2) {
                        this.changeRightViewer(1);
                    } else {
                        this.changeRightViewer(0);
                        this.changeSignatureBookLeftContent('visaCircuit');
                        this.leftDocumentDisplay = false;
                        this.letIconDisplay = false;
                    }
                }
                // END
            });
    }

    delAttachment(attachment: any) {
        if (this.canUpdateDocument) {
            let r = false;
            if (this.signatureBook.attachments.length <= 1) {
                r = confirm('Attention, ceci est votre dernière pièce jointe pour ce courrier, voulez-vous vraiment la supprimer ?');
            } else {
                r = confirm('Voulez-vous vraiment supprimer la pièce jointe ?');
            }
            if (r) {
                this.http.delete('../rest/attachments/' + attachment.res_id).pipe(
                    tap(() => {
                        this.refreshAttachments('del');
                    }),
                    catchError((err: any) => {
                        this.notify.handleErrors(err);
                        return of(false);
                    })
                ).subscribe();
            }
        }
    }

    signFile(attachment: any, signature: any) {
        if (!this.loadingSign && this.signatureBook.canSign) {
            this.loadingSign = true;
            const route = attachment.isResource ? '../rest/resources/' + attachment.res_id + '/sign' : '../rest/attachments/' + attachment.res_id + '/sign';
            this.http.put(route, { 'signatureId': signature.id })
                .subscribe((data: any) => {
                    if (!attachment.isResource) {
                        this.appDocumentViewer.loadRessource(data.id, 'attachment');
                        this.rightViewerLink = '../rest/attachments/' + data.id + '/content';
                        this.signatureBook.attachments[this.rightSelectedThumbnail].status = 'SIGN';
                        this.signatureBook.attachments[this.rightSelectedThumbnail].idToDl = data.new_id;
                        this.signatureBook.attachments[this.rightSelectedThumbnail].signed = true;
                        this.signatureBook.attachments[this.rightSelectedThumbnail].viewerId = data.id;
                    } else {
                        this.appDocumentViewer.loadRessource(attachment.res_id, 'mainDocument');
                        this.rightViewerLink += '?tsp=' + Math.floor(Math.random() * 100);
                        this.signatureBook.attachments[this.rightSelectedThumbnail].status = 'SIGN';
                    }
                    this.signatureBook.attachments[this.rightSelectedThumbnail].viewerLink = this.rightViewerLink;
                    let allSigned = true;
                    this.signatureBook.attachments.forEach((value: any) => {
                        if (value.sign && value.status !== 'SIGN') {
                            allSigned = false;
                        }
                    });
                    if (this.signatureBook.resList.length > 0) {
                        this.signatureBook.resList[this.signatureBook.resListIndex].allSigned = allSigned;
                    }

                    this.showSignaturesPanel = false;
                    this.loadingSign = false;
                }, (error: any) => {
                    this.notify.handleSoftErrors(error);
                    this.loadingSign = false;
                });
        }
    }

    unsignFile(attachment: any) {        
        if (attachment.isResource) {
            this.unSignMainDocument(attachment);
        } else {
            this.unSignAttachment(attachment);
        }
    }

    unSignMainDocument(attachment: any) {        
        this.http.put(`../rest/resources/${attachment.res_id}/unsign`, {}).pipe(
            tap(() => {
                this.appDocumentViewer.loadRessource(attachment.res_id, 'maintDocument');
                this.rightViewerLink += '?tsp=' + Math.floor(Math.random() * 100);
                this.signatureBook.attachments[this.rightSelectedThumbnail].status = 'A_TRA';

                if (this.signatureBook.resList.length > 0) {
                    this.signatureBook.resList[this.signatureBook.resListIndex].allSigned = false;
                }

                if (this.headerTab === 'visaCircuit') {
                    this.changeSignatureBookLeftContent('document');
                    setTimeout(() => {
                        this.changeSignatureBookLeftContent('visaCircuit');
                    }, 0);
                }
            }),
            catchError((err: any) => {
                if (err.status === 403) {
                    this.notify.error(this.translate.instant('lang.youCannotUnsign'));
                } else {
                    this.notify.handleSoftErrors(err);
                }
                return of(false);
            })
        ).subscribe();
    }

    unSignAttachment(attachment: any) {        
        this.http.put('../rest/attachments/' + attachment.res_id + '/unsign', {}).pipe(
            tap(() => {
                this.appDocumentViewer.loadRessource(attachment.res_id, 'attachment');
                this.rightViewerLink = '../rest/attachments/' + attachment.res_id + '/content';
                this.signatureBook.attachments[this.rightSelectedThumbnail].viewerLink = this.rightViewerLink;
                this.signatureBook.attachments[this.rightSelectedThumbnail].status = 'A_TRA';
                this.signatureBook.attachments[this.rightSelectedThumbnail].idToDl = attachment.res_id;
                this.signatureBook.attachments[this.rightSelectedThumbnail].signed = false;
                this.signatureBook.attachments[this.rightSelectedThumbnail].viewerId = attachment.res_id;
                if (this.signatureBook.resList.length > 0) {
                    this.signatureBook.resList[this.signatureBook.resListIndex].allSigned = false;
                }
                if (this.headerTab === 'visaCircuit') {
                    this.changeSignatureBookLeftContent('document');
                    setTimeout(() => {
                        this.changeSignatureBookLeftContent('visaCircuit');
                    }, 0);
                }
            }),
            catchError((err: any) => {
                if (err.status === 403) {
                    this.notify.error(this.translate.instant('lang.youCannotUnsign'));
                } else {
                    this.notify.handleSoftErrors(err);
                }
                return of(false);
            })
        ).subscribe();
    }

    backToBasket() {
        const path = '/basketList/users/' + this.userId + '/groups/' + this.groupId + '/baskets/' + this.basketId;
        this.router.navigate([path]);
    }

    backToDetails() {
        this.http.put('../rest/resourcesList/users/' + this.userId + '/groups/' + this.groupId + '/baskets/' + this.basketId + '/unlock', { resources: [this.resId] })
            .subscribe((data: any) => {
                this.router.navigate([`/resources/${this.resId}`]);
            }, (err: any) => { });
    }

    async changeLocation(resId: number, origin: string) {
        if (resId !== this.resId) {
            const data: any = await this.actionService.canExecuteAction([resId], this.userId, this.groupId, this.basketId);
            if (data === true) {
                this.actionService.stopRefreshResourceLock();
                if (!this.actionService.actionEnded) { 
                    this.actionService.unlockResource(this.userId, this.groupId, this.basketId, [this.resId]);
                }
                const path = 'signatureBook/users/' + this.userId + '/groups/' + this.groupId + '/baskets/' + this.basketId + '/resources/' + resId;
                this.router.navigate([path]);
            } else {
                this.backToBasket();
            }
        }
    }

    validForm() {
        if ($('#signatureBookActions option:selected').val() !== '') {
            this.processAction();
        } else {
            alert('Aucune action choisie');
        }
    }

    processAction() {
        this.http.get(`../rest/resources/${this.resId}?light=true`).pipe(
            tap((data: any) => {
                const actionId = $('#signatureBookActions option:selected').val();
                const selectedAction = this.signatureBook.actions.filter((action: any) => action.id == actionId)[0];
                this.actionService.launchAction(selectedAction, this.userId, this.groupId, this.basketId, [this.resId], data, false);
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    refreshBadge(nbRres: any, id: string) {
        this.processTool.filter(tool => tool.id === id)[0].count = nbRres;
    }

    loadBadges() {
        
        this.http.get(`../rest/resources/${this.resId}/items`).pipe(
            tap((data: any) => {
                console.debug(data)
                this.processTool.forEach(element => {
                    element.count = data[element.id] !== undefined ? data[element.id] : 0;
                });
            }),
            catchError((err: any) => {
                /**
                * SGAMI-SO #75
                */
                 console.debug("sgami load badges")
                 /**SGAMO-SO #75 */
                 this.actionService.nameBasket(this.basketId,this.userId)
                 this.basket = this.actionService.basket
                 console.debug('this.basket',this.basket)
                 //SGAMI-SO FIN 
                if( String(this.basket) == 'EvisBasket' || String(this.basket) == 'EsigBasket') {                    
                  
                    this.actionService.stopRefreshResourceLock();
                    /*if (!this.actionService.actionEnded) { 
                        this.actionService.unlockResource(this.userId, this.groupId, this.basketId, [this.resId]);
                    }*/
                    let path
                    if(this.actionService.sgami75.length > 0) {
                        path  = '/signatureBook/users/'+this.userId+'/groups/'+this.groupId+'/baskets/'+this.basketId+'/resources/' + this.actionService.sgami75[0]
                        
                    }else{
                        path = '/home'
                    }
                    this.notify.success(this.translate.instant('lang.avisWorkflowUpdated'));
                    setTimeout(() => {
                        this.router.navigate([path]);
                    }, 2000);
                   
                    
                    return of(true);
                    
                } else {
                    const path: string = `resourcesList/users/${this.userId}/groups/${this.groupId}/baskets/${this.basketId}?limit=10&offset=0`;
                    this.http.get(`../rest/${path}`).pipe(
                        tap((data: any) => {
                            if (!this.router.url.includes('signatureBook')) {
                                this.dialogRef?.close(data.allResources[0]);
                            } else {
                                if (data.count > 0) {
                                    this.dialogRef?.close();
                                    this.router.navigate(['/signatureBook/users/' + this.userId + '/groups/' + this.groupId + '/baskets/' + this.basketId + '/resources/' + data.allResources[0]])
                                } else {
                                    this.router.navigate(['/home']);
                                    this.notify.handleSoftErrors(err);
                                }
                            }
                        })
                    ).subscribe();
                }
                /**
                 * SGAMI-SO FIN
                 */
               
                return of(false);
            })
        ).subscribe();
    }

    createAttachment() {
        this.dialogRef = this.dialog.open(AttachmentCreateComponent, { disableClose: true, panelClass: 'attachment-modal-container', height: '90vh', width: '90vw', data: { resIdMaster: this.resId } });

        this.dialogRef.afterClosed().pipe(
            filter((data: string) => data === 'success'),
            tap(() => {
                this.refreshAttachments('add');
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    showAttachment(attachment: any) {
        if (this.canUpdateDocument && attachment.status !== 'SIGN') {
            if (attachment.isResource) {
                this.appDocumentViewer.editResource();
            } else {
                this.dialogRef = this.dialog.open(AttachmentPageComponent, { height: '99vh', width: this.appService.getViewMode() ? '99vw' : '90vw', maxWidth: this.appService.getViewMode() ? '99vw' : '90vw', panelClass: 'attachment-modal-container', disableClose: true, data: { resId: attachment.res_id } });

                this.dialogRef.afterClosed().pipe(
                    filter((data: string) => data === 'success'),
                    tap(() => {
                        this.refreshAttachments('edit');
                    }),
                    catchError((err: any) => {
                        this.notify.handleErrors(err);
                        return of(false);
                    })
                ).subscribe();
            }
        }
    }

    saveVisaWorkflow() {
        this.appVisaWorkflow.saveVisaWorkflow();
    }

    downloadOriginalFile(resId: any) {
        const downloadLink = document.createElement('a');
        this.http.get(`../rest/attachments/${resId}/originalContent?mode=base64`).pipe(
            tap((data: any) => {
                downloadLink.href = `data:${data.mimeType};base64,${data.encodedDocument}`;
                downloadLink.setAttribute('download', `${resId}.${data.extension}`);
                document.body.appendChild(downloadLink);
                downloadLink.click();
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    ngOnDestroy() {
        this.actionService.stopRefreshResourceLock();

        if (!this.actionService.actionEnded) {
            this.actionService.unlockResource(this.userId, this.groupId, this.basketId, [this.resId]);
        }

        // unsubscribe to ensure no memory leaks
        this.subscription.unsubscribe();
    }

    pdfViewerError(viewerLink: any) {
        this.http.get(viewerLink)
            .pipe(
                catchError((err: any) => {
                    if (err.status !== 200) {
                        this.notify.handleSoftErrors(err);
                    }
                    return of(false);
                })
            ).subscribe();
    }

    isToolEnabled(id: string) {
        if (id === 'history') {
            if (!this.privilegeService.hasCurrentUserPrivilege('view_full_history') && !this.privilegeService.hasCurrentUserPrivilege('view_doc_history')) {
                return false;
            } else {
                return true;
            }
        } else {
            return true;
        }
    }
}
