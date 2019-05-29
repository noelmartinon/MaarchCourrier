import { Pipe, PipeTransform, Component, OnInit, NgZone, ViewChild, HostListener } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { DomSanitizer } from '@angular/platform-browser';
import { Router, ActivatedRoute } from '@angular/router';
import { LANG } from './translate.component';
import { NotificationService } from './notification.service';

declare function lockDocument(resId: number) : void;
declare function unlockDocument(resId: number) : void;
declare function valid_action_form(a1: string, a2: string, a3: string, a4: number, a5: string, a6: string, a7: string, a8: string, a9: boolean, a10: any) : void;
declare function $j(selector: string) : any;
declare function showAttachmentsForm(path: string) : void;
declare function modifyAttachmentsForm(path: string, width: string, height: string) : void;
declare function setSessionForSignatureBook(resId: any) : void;
declare function triggerAngular(route: string) : void;

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
        currentAction           : {},
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
    headerTab                   : number    = 1;
    showTopRightPanel           : boolean   = false;
    showTopLeftPanel            : boolean   = false;
    showResLeftPanel            : boolean   = true;
    showLeftPanel               : boolean   = true;
    showRightPanel              : boolean   = true;
    showAttachmentPanel         : boolean   = false;
    showSignaturesPanel         : boolean   = false;
    loading                     : boolean   = false;
    loadingSign                 : boolean   = false;

    leftContentWidth            : string    = "44%";
    rightContentWidth           : string    = "44%";

    notesViewerLink             : string    = "";
    visaViewerLink              : string    = "";
    histViewerLink              : string    = "";
    linksViewerLink             : string    = "";
    attachmentsViewerLink       : string    = "";

    constructor(public http: HttpClient, private route: ActivatedRoute, private router: Router, private zone: NgZone, private notify: NotificationService) {
        
        $j("head style").remove();
        if ($j("link[href='merged_css.php']").length == 0) {
            var head = document.getElementsByTagName('head')[0];
            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'merged_css.php';
            link.type = 'text/css';
            link.media = 'screen';
            head.insertBefore(link,head.children[5])
        }
        window['angularSignatureBookComponent'] = {
            componentAfterAttach: (value: string) => this.processAfterAttach(value),
            componentAfterAction: () => this.processAfterAction(),
            componentAfterNotes: () => this.processAfterNotes(),
            componentAfterLinks: () => this.processAfterLinks()
        };
        (<any>window).pdfWorkerSrc = '../../node_modules/pdfjs-dist/build/pdf.worker.min.js';
    }

    prepareSignatureBook() {
        $j('main-header').remove();
        $j('#container').width("99%");
    }

    ngOnInit() : void {
        this.prepareSignatureBook();
        this.coreUrl = angularGlobals.coreUrl;

        this.loading = true;

        this.route.params.subscribe(params => {
            this.resId      = +params['resId'];
            this.basketId   = params['basketId'];
            this.groupId    = params['groupId'];
            this.userId    = params['userId'];

            this.signatureBook.resList = []; // This line is added because of manage action behaviour (processAfterAction is called twice)
            lockDocument(this.resId);
            setInterval(() => {lockDocument(this.resId)}, 50000);
            this.http.get("../../rest/signatureBook/users/" + this.userId + "/groups/" + this.groupId + "/baskets/" + this.basketId + "/resources/" + this.resId)
                .subscribe((data : any) => {
                    if (data.error) {
                        location.hash = "";
                        location.search = "";
                        return;
                    }
                    this.signatureBook = data;

                    this.headerTab              = 1;
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
                    this.notesViewerLink = "index.php?display=true&module=notes&page=notes&identifier=" + this.resId + "&origin=document&coll_id=letterbox_coll&load&size=full";
                    this.visaViewerLink  = "index.php?display=true&page=show_visa_tab&module=visa&resId=" + this.resId + "&collId=letterbox_coll&visaStep=true";
                    this.histViewerLink  = "index.php?display=true&page=show_history_tab&resId=" + this.resId + "&collId=letterbox_coll";
                    this.linksViewerLink = "index.php?display=true&page=show_links_tab&id=" + this.resId;
                    this.attachmentsViewerLink = "index.php?display=true&module=attachments&page=frame_list_attachments&resId=" + this.resId + "&noModification=true&template_selected=documents_list_attachments_simple&load&attach_type_exclude=converted_pdf,print_folder";

                    this.leftContentWidth  = "44%";
                    this.rightContentWidth = "44%";
                    if (this.signatureBook.documents[0]) {
                        this.leftViewerLink = this.signatureBook.documents[0].viewerLink;
                        if (this.signatureBook.documents[0].category_id == "outgoing") {
                            this.headerTab = 3;
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
                }, (err) => {
                    this.notify.error(err.error.errors);
                    setTimeout(() => {
                        this.backToBasket();
                    }, 2000);

                });
        });
    }

    ngOnDestroy() : void {
        delete window['angularSignatureBookComponent'];
    }

    processAfterAttach(mode: string) {
        this.zone.run(() => this.refreshAttachments(mode));
    }

    processAfterNotes() {
        this.zone.run(() => this.refreshNotes());
    }

    processAfterLinks() {
        this.zone.run(() => this.refreshLinks());
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

    changeSignatureBookLeftContent(id: number) {
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
        //this.reloadViewerRight();
    }

    changeLeftViewer(index: number) {
        this.leftViewerLink = this.signatureBook.documents[index].viewerLink;
        this.leftSelectedThumbnail = index;
        //this.reloadViewerLeft();
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
                $j("#hideLeftContent").css('background', '#F2F2F2');
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

    addAttachmentIframe() {
        showAttachmentsForm('index.php?display=true&module=attachments&page=attachments_content&docId=' + this.resId);
    }

    editAttachmentIframe(attachment: any) {
        if (attachment.canModify && attachment.status != "SIGN") {
            var resId: number;
            if (attachment.res_id == 0) {
                resId = attachment.res_id_version;
            } else if (attachment.res_id_version == 0) {
                resId = attachment.res_id;
            }

            modifyAttachmentsForm('index.php?display=true&module=attachments&page=attachments_content&id=' + resId + '&relation=' + attachment.relation + '&docId=' + this.resId, '98%', 'auto');
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
                var resId: number;
                if (attachment.res_id == 0) {
                    resId = attachment.res_id_version;
                } else if (attachment.res_id_version == 0) {
                    resId = attachment.res_id;
                }

                this.http.get('index.php?display=true&module=attachments&page=del_attachment&id=' + resId + '&relation=' + attachment.relation + '&docId=' + this.resId + '&rest=true')
                    .subscribe(() => {
                        this.refreshAttachments('del');
                    });
            }
        }
    }

    refreshNotes() {
        this.http.get(this.coreUrl + 'rest/res/' + this.resId + '/notes/count')
            .subscribe((data : any) => {
                this.signatureBook.nbNotes = data;
            });
    }

    refreshLinks() {
        this.http.get(this.coreUrl + 'rest/links/resId/' + this.resId)
            .subscribe((data : any) => {
                this.signatureBook.nbLinks = data.length;
            });
    }

    signFile(attachment: any, signature: any) {
        if (!this.loadingSign && this.signatureBook.canSign) {
            this.loadingSign = true;
            var path = "index.php?display=true&module=visa&page=sign_file&collId=letterbox_coll&resIdMaster=" + this.resId + "&signatureId=" + signature.id;

            if (attachment.res_id == 0) {
                if (attachment.attachment_type == "outgoing_mail" && this.signatureBook.documents[0].category_id == "outgoing") {
                    path += "&isVersion&isOutgoing&id=" + attachment.res_id_version;
                } else {
                    path += "&isVersion&id=" + attachment.res_id_version;
                }
            } else if (attachment.res_id_version == 0) {
                if (attachment.attachment_type == "outgoing_mail" && this.signatureBook.documents[0].category_id == "outgoing") {
                    path += "&isOutgoing&id=" + attachment.res_id;
                } else {
                    path += "&id=" + attachment.res_id;
                }
            }

            this.http.get(path, signature)
                .subscribe((data : any) => {
                    if (data.status == 0) {
                        this.rightViewerLink = "../../rest/res/" + this.resId + "/attachments/" + data.new_id + "/content";
                        this.signatureBook.attachments[this.rightSelectedThumbnail].viewerLink = this.rightViewerLink;
                        this.signatureBook.attachments[this.rightSelectedThumbnail].status = 'SIGN';
                        this.signatureBook.attachments[this.rightSelectedThumbnail].idToDl = data.new_id;
                        var allSigned = true;
                        this.signatureBook.attachments.forEach((value: any) => {
                            if (value.sign && value.status != 'SIGN') {
                                allSigned = false;
                            }
                        });
                        if (this.signatureBook.resList.length > 0) {
                            this.signatureBook.resList[this.signatureBook.resListIndex].allSigned = allSigned;
                        }

                        if(this.headerTab==3){
                            this.changeSignatureBookLeftContent(0);
                            setTimeout(() => {
                                this.changeSignatureBookLeftContent(3);
                            }, 0);
                        }
                    } else {
                        alert(data.error);
                    }

                    this.showSignaturesPanel = false;
                    this.loadingSign = false;
                });
        }
    }

    unsignFile(attachment: any) {
        var collId: string;
        var resId: number;
        var isVersion: string;

        if (attachment.res_id == 0) {
            resId = attachment.res_id_version;
            collId = "res_version_attachments";
            isVersion = "true";
        } else if (attachment.res_id_version == 0) {
            resId = attachment.res_id;
            collId = "res_attachments";
            isVersion = "false";
        }

        this.http.put(this.coreUrl + 'rest/signatureBook/' + resId + '/unsign', {'table' : collId})
            .subscribe(() => {
                this.rightViewerLink = "../../rest/res/" + this.resId + "/attachments/" + resId + "/content";
                this.signatureBook.attachments[this.rightSelectedThumbnail].viewerLink = this.rightViewerLink;
                this.signatureBook.attachments[this.rightSelectedThumbnail].status = 'A_TRA';
                this.signatureBook.attachments[this.rightSelectedThumbnail].idToDl = resId;
                if (this.signatureBook.resList.length > 0) {
                    this.signatureBook.resList[this.signatureBook.resListIndex].allSigned = false;
                }
                if(this.headerTab==3){
                    this.changeSignatureBookLeftContent(0);
                    setTimeout(() => {
                        this.changeSignatureBookLeftContent(3);
                    }, 0);
                }

            });

    }

    backToBasket() {
        this.http.put('../../rest/resourcesList/users/' + this.userId + '/groups/' + this.groupId + '/baskets/' + this.basketId + '/unlock', { resources: [this.resId] })
            .subscribe((data: any) => {
                window.location.href = 'index.php?page=view_baskets&module=basket&basketId='+this.basketId+'&userId='+this.userId+'&groupIdSer='+this.groupId+'&backToBasket=true';
            }, (err: any) => {
                window.location.href = 'index.php?page=view_baskets&module=basket&basketId='+this.basketId+'&userId='+this.userId+'&groupIdSer='+this.groupId+'&backToBasket=true';
            });
    }

    backToDetails() {
        this.http.put('../../rest/resourcesList/users/' + this.userId + '/groups/' + this.groupId + '/baskets/' + this.basketId + '/unlock', { resources: [this.resId] })
            .subscribe((data: any) => {
                location.hash = "";
                location.search = "?page=details&dir=indexing_searching&id=" + this.resId;
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
            this.sendActionForm();
        } else {
            alert("Aucune action choisie");
        }
    }

    sendActionForm() {
        unlockDocument(this.resId);

        setSessionForSignatureBook(this.resId);
        valid_action_form(
            'empty',
            'index.php?display=true&page=manage_action&module=core',
            this.signatureBook.currentAction.id,
            this.resId,
            'res_letterbox',
            'null',
            'letterbox_coll',
            'page',
            false,
            [$j("#signatureBookActions option:selected")[0].value]
        );
    }

}
