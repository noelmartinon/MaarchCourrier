import { Pipe, PipeTransform, Component, OnInit, NgZone, ViewChild, HostListener } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { DomSanitizer } from '@angular/platform-browser';
import { Router, ActivatedRoute } from '@angular/router';
import { LANG } from './translate.component';
import { NotificationService } from './notification.service';
import { tap, catchError, filter } from 'rxjs/operators';
import { of, Subscription } from 'rxjs';
import { PrivilegeService } from '../service/privileges.service';
import { MatDialogRef, MatDialog } from '@angular/material';
import { AttachmentCreateComponent } from './attachments/attachment-create/attachment-create.component';
import { FunctionsService } from '../service/functions.service';
import { AttachmentPageComponent } from './attachments/attachments-page/attachment-page.component';
import { VisaWorkflowComponent } from './visa/visa-workflow.component';
import { ActionsService } from './actions/actions.service';
import { HeaderService } from '../service/header.service';
import { AppService } from '../service/app.service';

declare function $j(selector: string) : any;

declare var angularGlobals : any;


@Pipe({ name: 'safeUrl' })
export class SafeUrlPipe implements PipeTransform {
    constructor(private sanitizer: DomSanitizer) {}
    transform(url: string) {
        return this.sanitizer.bypassSecurityTrustResourceUrl(url);
    }
}

@Component({
    templateUrl: "signature-book.component.html",
    styleUrls: ['signature-book.component.scss'],
    providers: [NotificationService]
})
export class SignatureBookComponent implements OnInit {

    coreUrl                     : string;
    resId                       : number;
    basketId                    : number;
    groupId                     : number;
    userId                     : number;
    lang                        : any       = LANG;

    signatureBook: any = {
        consigne                : "",
        documents               : [],
        attachments             : [],
        resList                 : [],
        resListIndex            : 0,
        lang                    : {}
    };

    rightSelectedThumbnail      : number    = 0;
    leftSelectedThumbnail       : number    = 0;
    rightViewerLink             : string    = "";
    leftViewerLink              : string    = "";
    headerTab                   : string    = "document";
    showTopRightPanel           : boolean   = false;
    showTopLeftPanel            : boolean   = false;
    showResLeftPanel            : boolean   = true;
    showLeftPanel               : boolean   = true;
    showRightPanel              : boolean   = true;
    showAttachmentPanel         : boolean   = false;
    showSignaturesPanel         : boolean   = false;
    loading                     : boolean   = false;
    loadingSign                 : boolean   = false;

    subscription: Subscription;
    currentResourceLock: any = null;

    leftContentWidth            : string    = "44%";
    rightContentWidth           : string    = "44%";
    dialogRef: MatDialogRef<any>;
    
    processTool: any[] = [
        {
            id: 'notes',
            icon: 'fas fa-pen-square fa-2x',
            label: this.lang.notesAlt,
            count: 0
        },
        {
            id: 'visaCircuit',
            icon: 'fas fa-list-ol fa-2x',
            label: this.lang.visaWorkflow,
            count: 0
        },
        {
            id: 'history',
            icon: 'fas fa-history fa-2x',
            label: this.lang.history,
            count: 0
        },
        {
            id: 'linkedResources',
            icon: 'fas fa-link fa-2x',
            label: this.lang.links,
            count: 0
        }
    ];

    @ViewChild('appVisaWorkflow', { static: false }) appVisaWorkflow: VisaWorkflowComponent;

    constructor(
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
        (<any>window).pdfWorkerSrc = '../../node_modules/pdfjs-dist/build/pdf.worker.min.js';

        // Event after process action 
        this.subscription = this.actionService.catchAction().subscribe(message => {
            clearInterval(this.currentResourceLock);
            this.processAfterAction();
        });
    }

    ngOnInit() : void {
        this.coreUrl = angularGlobals.coreUrl;

        this.loading = true;

        this.route.params.subscribe(params => {
            this.resId      = +params['resId'];
            this.basketId   = params['basketId'];
            this.groupId    = params['groupId'];
            this.userId    = params['userId'];

            this.signatureBook.resList = []; // This line is added because of manage action behaviour (processAfterAction is called twice)
            this.lockResource();
            this.http.get("../../rest/signatureBook/users/" + this.userId + "/groups/" + this.groupId + "/baskets/" + this.basketId + "/resources/" + this.resId)
                .subscribe((data : any) => {
                    if (data.error) {
                        location.hash = "";
                        location.search = "";
                        return;
                    }
                    this.signatureBook = data;

                    this.headerTab              = "document";
                    this.leftSelectedThumbnail  = 0;
                    this.rightSelectedThumbnail = 0;
                    this.leftViewerLink         = "";
                    this.rightViewerLink        = "";
                    this.showLeftPanel          = true;
                    this.showRightPanel         = true;
                    this.showResLeftPanel       = true;
                    this.showTopLeftPanel       = false;
                    this.showTopRightPanel      = false;
                    this.showAttachmentPanel    = false;

                    this.leftContentWidth  = "44%";
                    this.rightContentWidth = "44%";
                    if (this.signatureBook.documents[0]) {
                        this.leftViewerLink = this.signatureBook.documents[0].viewerLink;
                        if (this.signatureBook.documents[0].inSignatureBook) {
                            this.headerTab = "visaCircuit";
                        }
                    }
                    if (this.signatureBook.attachments[0]) {
                        this.rightViewerLink = this.signatureBook.attachments[0].viewerLink;
                    }

                    this.signatureBook.resListIndex = this.signatureBook.resList.map((e:any) => { return e.res_id; }).indexOf(this.resId);

                    this.displayPanel("RESLEFT");
                    this.loading = false;

                    setTimeout(() => {
                        $j("#rightPanelContent").niceScroll({touchbehavior:false, cursorcolor:"#666", cursoropacitymax:0.6, cursorwidth:4});
                        if ($j(".tooltipstered").length == 0) {
                            $j("#obsVersion").tooltipster({
                                interactive: true
                            });
                        }
                    }, 0);
                    this.loadBadges();
                    this.loadActions();
                }, (err) => {
                    this.notify.error(err.error.errors);
                    setTimeout(() => {
                        this.backToBasket();
                    }, 2000);

                });
        });
    }

    lockResource() {
        this.http.put(`../../rest/resourcesList/users/${this.userId}/groups/${this.groupId}/baskets/${this.basketId}/lock`, { resources: [this.resId] }).pipe(
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();

        this.currentResourceLock = setInterval(() => {
            this.http.put(`../../rest/resourcesList/users/${this.userId}/groups/${this.groupId}/baskets/${this.basketId}/lock`, { resources: [this.resId] }).pipe(
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        }, 50000);
    }

    unlockResource() {
        clearInterval(this.currentResourceLock);

        this.http.put(`../../rest/resourcesList/users/${this.userId}/groups/${this.groupId}/baskets/${this.basketId}/unlock`, { resources: [this.resId] }).pipe(
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }
    
    loadActions() {
        this.http.get("../../rest/resourcesList/users/" + this.userId + "/groups/" + this.groupId + "/baskets/" + this.basketId + "/actions?resId=" + this.resId)
        .subscribe((data : any) => {
            this.signatureBook.actions = data.actions;
        }, (err) => {
            this.notify.error(err.error.errors);
        });
    }

    processAfterAttach(mode: string) {
        this.zone.run(() => this.refreshAttachments(mode));
    }

    processAfterAction() {
        var idToGo = -1;
        var c = this.signatureBook.resList.length;

        for (let i = 0; i < c; i++) {
            if (this.signatureBook.resList[i].res_id == this.resId) {
                if (this.signatureBook.resList[i + 1]) {
                    idToGo = this.signatureBook.resList[i + 1].res_id;
                } else if (i > 0) {
                    idToGo = this.signatureBook.resList[i - 1].res_id;
                }
            }
        }

        if (c > 0) { // This (if)line is added because of manage action behaviour (processAfterAction is called twice)
            if (idToGo >= 0) {
                $j("#send").removeAttr("disabled");
                $j("#send").css("opacity", "1");
                this.zone.run(() => this.changeLocation(idToGo, "action"));
            } else {
                this.zone.run(() => this.backToBasket());
            }
        }
    }

    changeSignatureBookLeftContent(id: string) {
        this.headerTab = id;
        this.showTopLeftPanel = false;
    }

    changeRightViewer(index: number) {
        this.showAttachmentPanel = false;
        if (this.signatureBook.attachments[index]) {
            this.rightViewerLink = this.signatureBook.attachments[index].viewerLink;
        } else {
            this.rightViewerLink = "";
        }
        this.rightSelectedThumbnail = index;
    }

    changeLeftViewer(index: number) {
        this.leftViewerLink = this.signatureBook.documents[index].viewerLink;
        this.leftSelectedThumbnail = index;
    }

    displayPanel(panel: string) {
        if (panel == "TOPRIGHT") {
            this.showTopRightPanel = !this.showTopRightPanel;
        } else if (panel == "TOPLEFT") {
            this.showTopLeftPanel = !this.showTopLeftPanel;
        } else if (panel == "LEFT") {
            this.showLeftPanel = !this.showLeftPanel;
            this.showResLeftPanel = false;
            if (!this.showLeftPanel) {
                this.rightContentWidth = "96%";
                $j("#hideLeftContent").css('background', 'none');
            } else {
                this.rightContentWidth = "48%";
                this.leftContentWidth = "48%";
                $j("#hideLeftContent").css('background', '#fbfbfb');
            }
        } else if (panel == "RESLEFT") {
            this.showResLeftPanel = !this.showResLeftPanel;
            if (!this.showResLeftPanel) {
                this.rightContentWidth = "48%";
                this.leftContentWidth = "48%";
            } else {
                this.rightContentWidth = "44%";
                this.leftContentWidth = "44%";
                if (this.signatureBook.resList.length == 0 || typeof this.signatureBook.resList[0].creation_date === 'undefined') {
                    this.http.get("../../rest/signatureBook/users/" + this.userId + "/groups/" + this.groupId + "/baskets/" + this.basketId + "/resources")
                        .subscribe((data : any) => {
                            this.signatureBook.resList = data.resources;
                            this.signatureBook.resList.forEach((value: any, index: number) => {
                                if (value.res_id == this.resId) {
                                    this.signatureBook.resListIndex = index;
                                }
                            });
                            setTimeout(() => {
                                $j("#resListContent").niceScroll({touchbehavior:false, cursorcolor:"#666", cursoropacitymax:0.6, cursorwidth:4});
                                $j("#resListContent").scrollTop(0);
                                $j("#resListContent").scrollTop($j(".resListContentFrameSelected").offset().top - 42);
                            }, 0);
                        });
                }
            }
        } else if (panel == "MIDDLE") {
            this.showRightPanel = !this.showRightPanel;
            this.showResLeftPanel = false;
            if (!this.showRightPanel) {
                this.leftContentWidth = "96%";
                $j("#contentLeft").css('border-right', 'none');
            } else {
                this.rightContentWidth = "48%";
                this.leftContentWidth = "48%";
                $j("#contentLeft").css('border-right', 'solid 1px');
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

    refreshAttachments(mode: string) {
        if (mode == "rightContent") {
            this.http.get(this.coreUrl + 'rest/signatureBook/' + this.resId + '/incomingMailAttachments')
                .subscribe((data : any) => {
                    this.signatureBook.documents = data;
                });

        } else {
            this.http.get(this.coreUrl + 'rest/signatureBook/' + this.resId + '/attachments')
                .subscribe((data : any) => {
                    var i = 0;
                    if (mode == "add") {
                        var found = false;
                        data.forEach((elem: any, index: number) => {
                            if (!found && (!this.signatureBook.attachments[index] || elem.res_id != this.signatureBook.attachments[index].res_id)) {
                                i = index;
                                found = true;
                            }
                        });
                    } else if (mode == "edit") {
                        var id = this.signatureBook.attachments[this.rightSelectedThumbnail].res_id;
                        data.forEach((elem: any, index: number) => {
                            if (elem.res_id == id) {
                                i = index;
                            }
                        });
                    }

                    this.signatureBook.attachments = data;

                    if (mode == "add" || mode == "edit") {
                        this.changeRightViewer(i);
                    } else if (mode == "del") {
                        this.changeRightViewer(0);
                    }
                });
        }
    }

    delAttachment(attachment: any) {
        if (attachment.canDelete) {
            if (this.signatureBook.attachments.length <= 1) {
                var r = confirm('Attention, ceci est votre dernière pièce jointe pour ce courrier, voulez-vous vraiment la supprimer ?');
            } else {
                var r = confirm('Voulez-vous vraiment supprimer la pièce jointe ?');
            }
            if (r) {
                this.http.delete('../../rest/attachments/' + attachment.res_id).pipe(
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
            var route = attachment.isResource ? '../../rest/resources/' + attachment.res_id + '/sign' : '../../rest/attachments/' + attachment.res_id + '/sign';
            this.http.put(route, {'signatureId' : signature.id})
                .subscribe((data : any) => {
                    if (!attachment.isResource) {
                        this.rightViewerLink = "../../rest/attachments/" + data.id + "/content";
                        this.signatureBook.attachments[this.rightSelectedThumbnail].status = 'SIGN';
                        this.signatureBook.attachments[this.rightSelectedThumbnail].idToDl = data.new_id;
                    } else {
                        this.rightViewerLink += "?tsp=" + Math.floor(Math.random() * 100);
                    }
                    this.signatureBook.attachments[this.rightSelectedThumbnail].viewerLink = this.rightViewerLink;
                    var allSigned = true;
                    this.signatureBook.attachments.forEach((value: any) => {
                        if (value.sign && value.status != 'SIGN') {
                            allSigned = false;
                        }
                    });
                    if (this.signatureBook.resList.length > 0) {
                        this.signatureBook.resList[this.signatureBook.resListIndex].allSigned = allSigned;
                    }

                    this.showSignaturesPanel = false;
                    this.loadingSign = false;
                }, (error: any) => {
                    this.loadingSign = false;
                });
        }
    }

    unsignFile(attachment: any) {
        this.http.put('../../rest/attachments/' + attachment.res_id + '/unsign', {})
            .subscribe(() => {
                this.rightViewerLink = "../../rest/attachments/" + attachment.res_id + "/content";
                this.signatureBook.attachments[this.rightSelectedThumbnail].viewerLink = this.rightViewerLink;
                this.signatureBook.attachments[this.rightSelectedThumbnail].status = 'A_TRA';
                this.signatureBook.attachments[this.rightSelectedThumbnail].idToDl = attachment.res_id;
                if (this.signatureBook.resList.length > 0) {
                    this.signatureBook.resList[this.signatureBook.resListIndex].allSigned = false;
                }
                if(this.headerTab=="visaCircuit"){
                    this.changeSignatureBookLeftContent("document");
                    setTimeout(() => {
                        this.changeSignatureBookLeftContent("visaCircuit");
                    }, 0);
                }

            });
    }

    backToBasket() {
        let path = '/basketList/users/'+this.userId+'/groups/'+this.groupId+'/baskets/'+this.basketId;
        this.http.put('../../rest/resourcesList/users/' + this.userId + '/groups/' + this.groupId + '/baskets/' + this.basketId + '/unlock', { resources: [this.resId] })
            .subscribe((data: any) => {
                this.router.navigate([path]); 
            }, (err: any) => {
                this.router.navigate([path]); 
            });
    }

    backToDetails() {
        this.http.put('../../rest/resourcesList/users/' + this.userId + '/groups/' + this.groupId + '/baskets/' + this.basketId + '/unlock', { resources: [this.resId] })
            .subscribe((data: any) => {
                this.router.navigate([`/resources/${this.resId}`]);
            }, (err: any) => { });
        
    }

    changeLocation(resId: number, origin: string) {
        this.http.put('../../rest/resourcesList/users/' + this.userId + '/groups/' + this.groupId + '/baskets/' + this.basketId + '/lock', { resources: [resId] })
            .subscribe((data: any) => {
                if (data.lockedResources > 0) {
                    alert(data.lockedResources + ' ' + this.lang.warnLockRes + '.');
                } else {
                    let path = "signatureBook/users/" + this.userId + "/groups/" + this.groupId + "/baskets/" + this.basketId + "/resources/" + resId;
                    this.router.navigate([path]); 
                }
            }, (err: any) => {
                this.notify.handleErrors(err);
            });
    }

    validForm() {
        if ($j("#signatureBookActions option:selected")[0].value != "") {
            this.processAction();
        } else {
            alert("Aucune action choisie");
        }
    }

    processAction() {
        this.http.get(`../../rest/resources/${this.resId}?light=true`).pipe(
            tap((data: any) => {
                let actionId = $j("#signatureBookActions option:selected")[0].value;
                let selectedAction = this.signatureBook.actions.filter((action: any) => action.id == actionId)[0];
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
        this.http.get(`../../rest/resources/${this.resId}/items`).pipe(
            tap((data: any) => {
                this.processTool.forEach(element => {
                    element.count = data[element.id] !== undefined ? data[element.id] : 0;
                });
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
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
        if (attachment.canModify && attachment.status != "SIGN") {
            this.dialogRef = this.dialog.open(AttachmentPageComponent, { height: '99vh', width: this.appService.getViewMode() ? '99vw' : '90vw', maxWidth: this.appService.getViewMode() ? '99vw' : '90vw', panelClass: 'attachment-modal-container', disableClose: true, data: { resId: attachment.res_id} });
    
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

    saveVisaWorkflow() {
        this.appVisaWorkflow.saveVisaWorkflow();
    }

}
