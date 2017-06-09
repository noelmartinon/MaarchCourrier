import { Component, OnInit, NgZone } from '@angular/core';
import { Http } from '@angular/http';
import 'rxjs/add/operator/map';

declare function $j(selector: any) : any;
declare function disablePrototypeJS(method: string, plugins: any) : any;
declare function createModal(a: string, b: string, c: string, d: string) : any;
declare function autocomplete(a: number, b: string) : any;

declare var tinymce : any;
declare var Prototype : any;
declare var angularGlobals : any;


@Component({
    templateUrl : angularGlobals.profileView,
    styleUrls   : ['../../node_modules/bootstrap/dist/css/bootstrap.min.css', 'css/profile.component.css']
})
export class ProfileComponent implements OnInit {

    coreUrl                     : string;

    user                        : any       = {
        lang                    : {}
    };
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

    showPassword                : boolean   = false;
    selectedSignature           : number    = -1;
    selectedSignatureLabel      : string    = "";
    resultInfo                  : string    = "";
    loading                     : boolean   = false;


    constructor(public http: Http, private zone: NgZone) {
        window['angularProfileComponent'] = {
            componentAfterUpload: (base64Content: any) => this.processAfterUpload(base64Content),
        };
    }

    prepareProfile() {
        $j('#inner_content').remove();
        $j('#menunav').hide();
        $j('#divList').remove();
        $j('#magicContactsTable').remove();
        $j('#manageBasketsOrderTable').remove();
        $j('#controlParamTechnicTable').remove();
        $j('#container').width("99%");
        if ($j('#content h1')[0] && $j('#content h1')[0] != $j('my-app h1')[0]) {
            $j('#content h1')[0].remove();
        }

        if (Prototype.BrowserFeatures.ElementExtensions) {
            //FIX PROTOTYPE CONFLICT
            let pluginsToDisable = ['collapse', 'dropdown', 'modal', 'tooltip', 'popover','tab'];
            disablePrototypeJS('show', pluginsToDisable);
            disablePrototypeJS('hide', pluginsToDisable);
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

        this.http.get(this.coreUrl + 'rest/user/profile')
            .map(res => res.json())
            .subscribe((data) => {
                this.user = data;

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

            this.resultInfo = "Taille maximum de fichier dépassée (2 MB)";
            $j('#resultInfo').removeClass().addClass('alert alert-danger alert-dismissible');
            $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function(){
                $j("#resultInfo").slideUp(500);
            });
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

    getAbsenceInfos() {
        this.http.get(this.coreUrl + 'rest/currentUser/baskets/absence')
            .map(res => res.json())
            .subscribe((data) => {
                this.loading = false;
            });
    }

    updatePassword() {
        this.http.put(this.coreUrl + 'rest/currentUser/password', this.passwordModel)
            .map(res => res.json())
            .subscribe((data) => {
                if (data.errors) {
                    this.resultInfo = data.errors;
                    $j('#resultInfo').removeClass().addClass('alert alert-danger alert-dismissible');
                    $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function(){
                        $j("#resultInfo").slideUp(500);
                    });
                } else {
                    this.showPassword = false;
                    this.passwordModel = {
                        currentPassword         : "",
                        newPassword             : "",
                        reNewPassword           : "",
                    };
                    this.resultInfo = data.success;
                    $j('#resultInfo').removeClass().addClass('alert alert-success alert-dismissible');
                    //auto close
                    $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function(){
                        $j("#resultInfo").slideUp(500);
                    });
                }
            });
    }

    submitEmailSignature() {
        this.mailSignatureModel.htmlBody = tinymce.get('emailSignature').getContent();

        this.http.post(this.coreUrl + 'rest/currentUser/emailSignature', this.mailSignatureModel)
            .map(res => res.json())
            .subscribe((data) => {
                if (data.errors) {
                    this.resultInfo = data.errors;
                    $j('#resultInfo').removeClass().addClass('alert alert-danger alert-dismissible');
                    $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function(){
                        $j("#resultInfo").slideUp(500);
                    });
                } else {
                    this.user.emailSignatures = data.emailSignatures;
                    this.mailSignatureModel     = {
                        selected                : 0,
                        htmlBody                : "",
                        title                   : "",
                    };
                    tinymce.get('emailSignature').setContent("");
                    this.resultInfo = data.success;
                    $j('#resultInfo').removeClass().addClass('alert alert-success alert-dismissible');
                    $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function(){
                        $j("#resultInfo").slideUp(500);
                    }); 
                }
            });
    }

    updateEmailSignature() {
        this.mailSignatureModel.htmlBody = tinymce.get('emailSignature').getContent();
        var id = this.user.emailSignatures[this.mailSignatureModel.selected - 1].id;

        this.http.put(this.coreUrl + 'rest/currentUser/emailSignature/' + id, this.mailSignatureModel)
            .map(res => res.json())
            .subscribe((data) => {
                if (data.errors) {
                    this.resultInfo = data.errors;
                    $j('#resultInfo').removeClass().addClass('alert alert-danger alert-dismissible');
                    $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function(){
                        $j("#resultInfo").slideUp(500);
                    });
                } else {
                    this.user.emailSignatures[this.mailSignatureModel.selected - 1].title = data.emailSignature.title;
                    this.user.emailSignatures[this.mailSignatureModel.selected - 1].html_body = data.emailSignature.html_body;
                    this.resultInfo = data.success;
                    $j('#resultInfo').removeClass().addClass('alert alert-success alert-dismissible');
                    $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function(){
                        $j("#resultInfo").slideUp(500);
                    }); 
                }
            });
    }

    deleteEmailSignature() {
        let r = confirm('Voulez-vous vraiment supprimer la signature de mail ?');

        if (r) {
            var id = this.user.emailSignatures[this.mailSignatureModel.selected - 1].id;

            this.http.delete(this.coreUrl + 'rest/currentUser/emailSignature/' + id)
                .map(res => res.json())
                .subscribe((data) => {
                    if (data.errors) {
                        this.resultInfo = data.errors;
                        $j('#resultInfo').removeClass().addClass('alert alert-danger alert-dismissible');
                        $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function(){
                            $j("#resultInfo").slideUp(500);
                        });
                    } else {
                        this.user.emailSignatures = data.emailSignatures;
                        this.mailSignatureModel     = {
                            selected                : 0,
                            htmlBody                : "",
                            title                   : "",
                        };
                        tinymce.get('emailSignature').setContent("");
                        this.resultInfo = data.success;
                        $j('#resultInfo').removeClass().addClass('alert alert-success alert-dismissible');
                        $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function(){
                            $j("#resultInfo").slideUp(500);
                        }); 
                    }
                });
        }
    }

    submitSignature() {
        this.http.post(this.coreUrl + 'rest/currentUser/signature', this.signatureModel)
            .map(res => res.json())
            .subscribe((data) => {
                if (data.errors) {
                    this.resultInfo = data.errors;
                    $j('#resultInfo').removeClass().addClass('alert alert-danger alert-dismissible');
                    $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function(){
                        $j("#resultInfo").slideUp(500);
                    }); 
                } else {
                    this.user.signatures = data.signatures;
                    this.signatureModel  = {
                        base64                  : "",
                        base64ForJs             : "",
                        name                    : "",
                        type                    : "",
                        size                    : 0,
                        label                   : "",
                    };
                    this.resultInfo = data.success;
                    $j('#resultInfo').removeClass().addClass('alert alert-success alert-dismissible');
                    $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function(){
                        $j("#resultInfo").slideUp(500);
                    }); 
                }
            });
    }

    updateSignature() {
        var id = this.user.signatures[this.selectedSignature].id;

        this.http.put(this.coreUrl + 'rest/currentUser/signature/' + id, {"label" : this.selectedSignatureLabel})
            .map(res => res.json())
            .subscribe((data) => {
                if (data.errors) {
                    this.resultInfo = data.errors;
                    $j('#resultInfo').removeClass().addClass('alert alert-danger alert-dismissible');
                    $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function(){
                        $j("#resultInfo").slideUp(500);
                    });  
                } else {
                    this.user.signatures[this.selectedSignature].signature_label = data.signature.signature_label;
                    this.selectedSignature = -1;
                    this.selectedSignatureLabel = "";
                    this.resultInfo = data.success;
                    $j('#resultInfo').removeClass().addClass('alert alert-success alert-dismissible');
                    $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function(){
                        $j("#resultInfo").slideUp(500);
                    });  
                }
            });
    }

    deleteSignature(id: number) {
        let r = confirm('Voulez-vous vraiment supprimer la signature ?');

        if (r) {
            this.http.delete(this.coreUrl + 'rest/currentUser/signature/' + id)
                .map(res => res.json())
                .subscribe((data) => {
                    if (data.errors) {
                        this.resultInfo = data.errors;
                        $j('#resultInfo').removeClass().addClass('alert alert-danger alert-dismissible');
                        $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function(){
                            $j("#resultInfo").slideUp(500);
                        });  
                    } else {
                        this.user.signatures = data.signatures;
                        this.resultInfo = data.success;
                        $j('#resultInfo').removeClass().addClass('alert alert-success alert-dismissible');
                        $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function(){
                            $j("#resultInfo").slideUp(500);
                        });  
                    }
                });
        }
    }

    onSubmit() {
        this.http.put(this.coreUrl + 'rest/user/profile', this.user)
            .map(res => res.json())
            .subscribe((data) => {
                if (data.errors) {
                    this.resultInfo = data.errors;
                    $j('#resultInfo').removeClass().addClass('alert alert-danger alert-dismissible');
                    $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function(){
                        $j("#resultInfo").slideUp(500);
                    });
                            
                }else{
                    this.resultInfo = data.success;
                    $j('#resultInfo').removeClass().addClass('alert alert-success alert-dismissible');
                    //auto close
                    $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function(){
                        $j("#resultInfo").slideUp(500);
                    });   
                }
            }, (error) => {
                alert(error.statusText);
            });
    }

    absenceModal() {
        createModal(this.user.absence, 'modal_redirect', 'auto', '950px');
        autocomplete(this.user.countBasketsForAbsence, 'index.php?display=true&module=basket&page=autocomplete_users_list');
    }
}
