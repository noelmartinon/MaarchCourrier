import { Component, OnInit, NgZone, ViewChild } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from './translate.component';
import { NotificationService } from './notification.service';
import { MatPaginator, MatTableDataSource, MatSort } from '@angular/material';

declare function $j(selector: any) : any;

declare var tinymce : any;
declare var angularGlobals : any;


@Component({
    templateUrl : "../../../Views/profile.component.html",
    styleUrls   : ['../../../css/profile.component.css'],
    providers   : [NotificationService]
})
export class ProfileComponent implements OnInit {

    coreUrl                     : string;
    lang                        : any       = LANG;

    user                        : any       = {
        baskets                 : []
    };
    histories                   : any[]     = [];
    passwordModel               : any       = {
        currentPassword         : "",
        newPassword             : "",
        reNewPassword           : "",
    };
    signatureModel              : any       = {
        base64                  : "",
        base64ForJs             : "",
        name                    : "",
        type                    : "",
        size                    : 0,
        label                   : "",
    };
    mailSignatureModel          : any       = {
        selected                : 0,
        htmlBody                : "",
        title                   : "",
    };
    userAbsenceModel            : any[]     = [];
    basketsToRedirect           : string[]  = [];

    showPassword                : boolean   = false;
    selectedSignature           : number    = -1;
    selectedSignatureLabel      : string    = "";
    loading                     : boolean   = false;
    displayAbsenceButton        : boolean   = false;

    displayedColumns = ['event_date','info'];
    dataSource = new MatTableDataSource(this.histories);
    @ViewChild(MatPaginator) paginator: MatPaginator;
    @ViewChild(MatSort) sort: MatSort;
    applyFilter(filterValue: string) {
        filterValue = filterValue.trim(); // Remove whitespace
        filterValue = filterValue.toLowerCase(); // MatTableDataSource defaults to lowercase matches
        this.dataSource.filter = filterValue;
    }


    constructor(public http: HttpClient, private zone: NgZone, private notify: NotificationService) {
        if ($j("link[href='merged_css.php']").length == 0) {
            var head = document.getElementsByTagName('head')[0];
            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'merged_css.php';
            link.type = 'text/css';
            link.media = 'screen';
            head.insertBefore(link,head.children[5])
        }
        window['angularProfileComponent'] = {
            componentAfterUpload: (base64Content: any) => this.processAfterUpload(base64Content),
        };
    }

    prepareProfile() {
        $j('#inner_content').remove();
        $j('#inner_content_contact').parent('div').remove();
        $j('#inner_content_contact').remove();
        $j('#menunav').hide();
        $j('#divList').remove();
        $j('#magicContactsTable').remove();
        $j('#manageBasketsOrderTable').remove();
        $j('#controlParamTechnicTable').remove();
        $j('#container').width("99%");
        if ($j('#content h1')[0] && $j('#content h1')[0] != $j('my-app h1')[0]) {
            $j('#content h1')[0].remove();
        }

        //LOAD EDITOR TINYMCE for MAIL SIGN
        tinymce.baseURL = "../../node_modules/tinymce";
        tinymce.suffix = '.min';
        tinymce.init({
            selector: "textarea#emailSignature",
            statusbar : false,
            language : "fr_FR",
            language_url: "tools/tinymce/langs/fr_FR.js",
            height : "200",
            plugins: [
                "textcolor"
            ],
            external_plugins: {
                'bdesk_photo': "../../apps/maarch_entreprise/tools/tinymce/bdesk_photo/plugin.min.js"
            },
            menubar: false,
            toolbar: "undo | bold italic underline | alignleft aligncenter alignright | bdesk_photo | forecolor",
            theme_buttons1_add : "fontselect,fontsizeselect",
            theme_buttons2_add_before : "cut,copy,paste,pastetext,pasteword,separator,search,replace,separator",
            theme_buttons2_add : "separator,insertdate,inserttime,preview,separator,forecolor,backcolor",
            theme_buttons3_add_before : "tablecontrols,separator",
            theme_buttons3_add : "separator,print,separator,ltr,rtl,separator,fullscreen,separator,insertlayer,moveforward,movebackward,absolut",
            theme_toolbar_align : "left",
            theme_advanced_toolbar_location : "top",
            theme_styles : "Header 1=header1;Header 2=header2;Header 3=header3;Table Row=tableRow1"

        });

    }

    updateBreadcrumb(applicationName: string) {
        if ($j('#ariane')[0]) {
            $j('#ariane')[0].innerHTML = "<a href='index.php?reinit=true'>" + applicationName + "</a> > Profil";
        }
    }

    ngOnInit(): void {
        this.prepareProfile();
        this.updateBreadcrumb(angularGlobals.applicationName);
        this.coreUrl = angularGlobals.coreUrl;

        this.loading = true;

        this.http.get('../../rest/currentUser/profile')
            .subscribe((data : any) => {
                this.user = data;

                this.user.baskets.forEach((value: any, index: number) => {
                    this.user.baskets[index]['disabled'] = false;
                    this.user.redirectedBaskets.forEach((value2: any) => {
                        if (value.basket_id == value2.basket_id && value.basket_owner == value2.basket_owner) {
                            this.user.baskets[index]['disabled'] = true;
                        }
                    });
                });
                setTimeout(() => {
                    $j("#absenceUser").typeahead({
                        order: "asc",
                        display: "idToDisplay",
                        templateValue: "{{id}}",
                        source: {
                            ajax: {
                                type: "GET",
                                dataType: "json",
                                url: this.coreUrl + "rest/autocomplete/users",
                            }
                        }
                    });
                }, 0);

                this.loading = false;
            });
    }

    processAfterUpload(b64Content: any) {
        this.zone.run(() => this.resfreshUpload(b64Content));
    }

    resfreshUpload(b64Content: any) {
        if (this.signatureModel.size <= 2000000) {
            this.signatureModel.base64 = b64Content.replace(/^data:.*?;base64,/, "");
            this.signatureModel.base64ForJs = b64Content;
        } else {
            this.signatureModel.name = "";
            this.signatureModel.size = 0;
            this.signatureModel.type = "";
            this.signatureModel.base64 = "";
            this.signatureModel.base64ForJs = "";

            this.notify.error("Taille maximum de fichier dépassée (2 MB)");
        }
    }

    displayPassword() {
        this.showPassword = !this.showPassword;
    }

    clickOnUploader(id: string) {
        $j('#' + id).click();
    }

    uploadSignatureTrigger(fileInput: any) {
        if (fileInput.target.files && fileInput.target.files[0]) {
            var reader = new FileReader();

            this.signatureModel.name = fileInput.target.files[0].name;
            this.signatureModel.size = fileInput.target.files[0].size;
            this.signatureModel.type = fileInput.target.files[0].type;
            if (this.signatureModel.label == "") {
                this.signatureModel.label = this.signatureModel.name;
            }

            reader.readAsDataURL(fileInput.target.files[0]);

            reader.onload = function (value: any) {
                window['angularProfileComponent'].componentAfterUpload(value.target.result);
            };

        }
    }

    displaySignatureEditionForm(index: number) {
        this.selectedSignature = index;
        this.selectedSignatureLabel = this.user.signatures[index].signature_label;
    }

    changeEmailSignature() {
        var index = $j("#emailSignaturesSelect").prop("selectedIndex");
        this.mailSignatureModel.selected = index;

        if (index > 0) {
            tinymce.get('emailSignature').setContent(this.user.emailSignatures[index - 1].html_body);
            this.mailSignatureModel.title = this.user.emailSignatures[index - 1].title;
        } else {
            tinymce.get('emailSignature').setContent("");
            this.mailSignatureModel.title = "";
        }
    }

    addBasketRedirection() {
        if (typeof this.basketsToRedirect[0] != 'undefined' && $j("#absenceUser")[0].value) {
            var redirectModel :any[] = [];

            this.basketsToRedirect.forEach((value: any) => {
                redirectModel.push({
                    "basketId"      : this.user.baskets[value].basket_id,
                    "basketName"    : this.user.baskets[value].basket_name,
                    "virtual"       : this.user.baskets[value].is_virtual,
                    "basketOwner"   : this.user.baskets[value].basket_owner,
                    "newUser"       : $j("#absenceUser")[0].value,
                });
            });

            this.http.post(this.coreUrl + 'rest/users/' + this.user.id + '/redirectedBaskets', redirectModel)
                .subscribe((data: any) => {
                    $j('#selectBasketAbsenceUser option').prop('selected', false);
                    $j("#absenceUser")[0].value = "";
                    this.basketsToRedirect = [];

                    this.user.redirectedBaskets = data["redirectedBaskets"];
                    this.user.baskets.forEach((value: any, index: number) => {
                        this.user.baskets[index]['disabled'] = false;
                        this.user.redirectedBaskets.forEach((value2: any) => {
                            if (value.basket_id == value2.basket_id && value.basket_owner == value2.basket_owner) {
                                this.user.baskets[index]['disabled'] = true;
                            }
                        });
                    });
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        } else {
            this.notify.error("Veuillez sélectionner au moins une bannette et un utilisateur");
        }
    }

    delBasketRedirection(basket: any) {
        this.http.request('DELETE', this.coreUrl + "rest/users/" + this.user.id + "/redirectedBaskets/" + basket.basket_id, {body : {"basketOwner" : basket.basket_owner}})
            .subscribe((data: any) => {
                this.user.redirectedBaskets = data["redirectedBaskets"];
                this.user.baskets.forEach((value: any, index: number) => {
                    this.user.baskets[index]['disabled'] = false;
                    this.user.redirectedBaskets.forEach((value2: any) => {
                        if (value.basket_id == value2.basket_id && value.basket_owner == value2.basket_owner) {
                            this.user.baskets[index]['disabled'] = true;
                        }
                    });
                });

                this.notify.success(this.lang.modificationSaved);
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    updateBasketColor(i: number, y: number) {
        this.http.put(this.coreUrl + "rest/currentUser/groups/" + this.user.regroupedBaskets[i].groupId + "/baskets/" + this.user.regroupedBaskets[i].baskets[y].basket_id, {"color" : this.user.regroupedBaskets[i].baskets[y].color})
            .subscribe((data: any) => {
                this.user.regroupedBaskets = data.userBaskets;
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    activateAbsence() {
        let r = confirm('Voulez-vous vraiment activer votre absence ? Vous serez automatiquement déconnecté.');

        if (r) {
            this.http.put(this.coreUrl + 'rest/users/' + this.user.id + '/status', {"status" : "ABS"})
                .subscribe(() => {
                    location.hash = "";
                    location.search = "?display=true&page=logout&abs_mode";
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

    askRedirectBasket() {
        let r = confirm('Voulez-vous rediriger vos bannettes avant de vous mettre en absence ?');

        if (r) {
            this.displayAbsenceButton = true;
            $j('#redirectBasketCard').click();
        } else {
            this.activateAbsence();
        }
    }

    updatePassword() {
        this.http.put(this.coreUrl + 'rest/currentUser/password', this.passwordModel)
            .subscribe((data : any) => {
                this.showPassword = false;
                this.passwordModel = {
                    currentPassword         : "",
                    newPassword             : "",
                    reNewPassword           : "",
                };
                this.notify.success(this.lang.passwordUpdated);
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    submitEmailSignature() {
        this.mailSignatureModel.htmlBody = tinymce.get('emailSignature').getContent();

        this.http.post(this.coreUrl + 'rest/currentUser/emailSignature', this.mailSignatureModel)
            .subscribe((data : any) => {
                if (data.errors) {
                    this.notify.error(data.errors);
                } else {
                    this.user.emailSignatures = data.emailSignatures;
                    this.mailSignatureModel     = {
                        selected                : 0,
                        htmlBody                : "",
                        title                   : "",
                    };
                    tinymce.get('emailSignature').setContent("");
                    this.notify.success(this.lang.emailSignatureAdded);
                }
            });
    }

    updateEmailSignature() {
        this.mailSignatureModel.htmlBody = tinymce.get('emailSignature').getContent();
        var id = this.user.emailSignatures[this.mailSignatureModel.selected - 1].id;

        this.http.put(this.coreUrl + 'rest/currentUser/emailSignature/' + id, this.mailSignatureModel)
            .subscribe((data : any) => {
                if (data.errors) {
                    this.notify.error(data.errors);
                } else {
                    this.user.emailSignatures[this.mailSignatureModel.selected - 1].title = data.emailSignature.title;
                    this.user.emailSignatures[this.mailSignatureModel.selected - 1].html_body = data.emailSignature.html_body;
                    this.notify.success(this.lang.emailSignatureUpdated);
                }
            });
    }

    deleteEmailSignature() {
        let r = confirm('Voulez-vous vraiment supprimer la signature de mail ?');

        if (r) {
            var id = this.user.emailSignatures[this.mailSignatureModel.selected - 1].id;

            this.http.delete(this.coreUrl + 'rest/currentUser/emailSignature/' + id)
                .subscribe((data : any) => {
                    if (data.errors) {
                        this.notify.error(data.errors);
                    } else {
                        this.user.emailSignatures = data.emailSignatures;
                        this.mailSignatureModel     = {
                            selected                : 0,
                            htmlBody                : "",
                            title                   : "",
                        };
                        tinymce.get('emailSignature').setContent("");
                        this.notify.success(this.lang.emailSignatureDeleted);
                    }
                });
        }
    }

    submitSignature() {
        this.http.post(this.coreUrl + "rest/users/" + this.user.id + "/signatures", this.signatureModel)
            .subscribe((data : any) => {
                this.user.signatures = data.signatures;
                this.signatureModel  = {
                    base64                  : "",
                    base64ForJs             : "",
                    name                    : "",
                    type                    : "",
                    size                    : 0,
                    label                   : "",
                };
                this.notify.success(this.lang.signatureAdded);
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    updateSignature() {
        var id = this.user.signatures[this.selectedSignature].id;

        this.http.put(this.coreUrl + "rest/users/" + this.user.id + "/signatures/" + id, {"label" : this.selectedSignatureLabel})
            .subscribe((data : any) => {
                this.user.signatures[this.selectedSignature].signature_label = data.signature.signature_label;
                this.selectedSignature = -1;
                this.selectedSignatureLabel = "";
                this.notify.success(this.lang.signatureUpdated);
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    deleteSignature(id: number) {
        let r = confirm('Voulez-vous vraiment supprimer la signature ?');

        if (r) {
            this.http.delete(this.coreUrl + "rest/users/" + this.user.id + "/signatures/" + id)
                .subscribe((data : any) => {
                    this.user.signatures = data.signatures;
                    this.notify.success(this.lang.signatureDeleted);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

    getHistories() {
        if (this.histories.length == 0) {
            this.http.get(this.coreUrl + 'rest/histories/users/' + this.user.id)
                .subscribe((data : any) => {
                    this.histories = data.histories;
                    setTimeout(() => {
                        this.dataSource = new MatTableDataSource(this.histories);
                        this.dataSource.paginator = this.paginator;
                        this.dataSource.sort = this.sort;
                    }, 0);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

    onSubmit() {
        this.http.put(this.coreUrl + 'rest/currentUser/profile', this.user)
            .subscribe(() => {
                this.notify.success(this.lang.modificationSaved);
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }
}
