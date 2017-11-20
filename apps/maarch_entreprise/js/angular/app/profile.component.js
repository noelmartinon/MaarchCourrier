"use strict";
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
var __metadata = (this && this.__metadata) || function (k, v) {
    if (typeof Reflect === "object" && typeof Reflect.metadata === "function") return Reflect.metadata(k, v);
};
Object.defineProperty(exports, "__esModule", { value: true });
var core_1 = require("@angular/core");
var http_1 = require("@angular/http");
require("rxjs/add/operator/map");
var ProfileComponent = (function () {
    function ProfileComponent(http, zone) {
        var _this = this;
        this.http = http;
        this.zone = zone;
        this.user = {
            lang: {}
        };
        this.passwordModel = {
            currentPassword: "",
            newPassword: "",
            reNewPassword: "",
        };
        this.signatureModel = {
            base64: "",
            base64ForJs: "",
            name: "",
            type: "",
            size: 0,
            label: "",
        };
        this.mailSignatureModel = {
            selected: 0,
            htmlBody: "",
            title: "",
        };
        this.basketsToRedirect = [];
        this.showPassword = false;
        this.selectedSignature = -1;
        this.selectedSignatureLabel = "";
        this.resultInfo = "";
        this.loading = false;
        window['angularProfileComponent'] = {
            componentAfterUpload: function (base64Content) { return _this.processAfterUpload(base64Content); },
        };
    }
    ProfileComponent.prototype.prepareProfile = function () {
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
            var pluginsToDisable = ['collapse', 'dropdown', 'modal', 'tooltip', 'popover', 'tab'];
            disablePrototypeJS('show', pluginsToDisable);
            disablePrototypeJS('hide', pluginsToDisable);
        }
        //LOAD EDITOR TINYMCE for MAIL SIGN
        tinymce.baseURL = "../../node_modules/tinymce";
        tinymce.suffix = '.min';
        tinymce.init({
            selector: "textarea#emailSignature",
            statusbar: false,
            language: "fr_FR",
            language_url: "tools/tinymce/langs/fr_FR.js",
            height: "200",
            plugins: [
                "textcolor"
            ],
            external_plugins: {
                'bdesk_photo': "../../apps/maarch_entreprise/tools/tinymce/bdesk_photo/plugin.min.js"
            },
            menubar: false,
            toolbar: "undo | bold italic underline | alignleft aligncenter alignright | bdesk_photo | forecolor",
            theme_buttons1_add: "fontselect,fontsizeselect",
            theme_buttons2_add_before: "cut,copy,paste,pastetext,pasteword,separator,search,replace,separator",
            theme_buttons2_add: "separator,insertdate,inserttime,preview,separator,forecolor,backcolor",
            theme_buttons3_add_before: "tablecontrols,separator",
            theme_buttons3_add: "separator,print,separator,ltr,rtl,separator,fullscreen,separator,insertlayer,moveforward,movebackward,absolut",
            theme_toolbar_align: "left",
            theme_advanced_toolbar_location: "top",
            theme_styles: "Header 1=header1;Header 2=header2;Header 3=header3;Table Row=tableRow1"
        });
    };
    ProfileComponent.prototype.updateBreadcrumb = function (applicationName) {
        if ($j('#ariane')[0]) {
            $j('#ariane')[0].innerHTML = "<a href='index.php?reinit=true'>" + applicationName + "</a> > Profil";
        }
    };
    ProfileComponent.prototype.ngOnInit = function () {
        var _this = this;
        this.prepareProfile();
        this.updateBreadcrumb(angularGlobals.applicationName);
        this.coreUrl = angularGlobals.coreUrl;
        this.loading = true;
        this.http.get(this.coreUrl + 'rest/user/profile')
            .map(function (res) { return res.json(); })
            .subscribe(function (data) {
            _this.user = data;
            _this.user.baskets.forEach(function (value, index) {
                _this.user.baskets[index]['disabled'] = false;
                _this.user.redirectedBaskets.forEach(function (value2) {
                    if (value.basket_id == value2.basket_id && value.basket_owner == value2.basket_owner) {
                        _this.user.baskets[index]['disabled'] = true;
                    }
                });
            });
            setTimeout(function () {
                $j("#absenceUser").typeahead({
                    order: "asc",
                    source: {
                        ajax: {
                            type: "POST",
                            dataType: "json",
                            url: _this.coreUrl + "rest/users/autocompleter",
                        }
                    }
                });
            }, 0);
            _this.loading = false;
        });
    };
    ProfileComponent.prototype.processAfterUpload = function (b64Content) {
        var _this = this;
        this.zone.run(function () { return _this.resfreshUpload(b64Content); });
    };
    ProfileComponent.prototype.resfreshUpload = function (b64Content) {
        if (this.signatureModel.size <= 2000000) {
            this.signatureModel.base64 = b64Content.replace(/^data:.*?;base64,/, "");
            this.signatureModel.base64ForJs = b64Content;
        }
        else {
            this.signatureModel.name = "";
            this.signatureModel.size = 0;
            this.signatureModel.type = "";
            this.signatureModel.base64 = "";
            this.signatureModel.base64ForJs = "";
            this.resultInfo = "Taille maximum de fichier dépassée (2 MB)";
            $j('#resultInfo').removeClass().addClass('alert alert-danger alert-dismissible');
            $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function () {
                $j("#resultInfo").slideUp(500);
            });
        }
    };
    ProfileComponent.prototype.displayPassword = function () {
        this.showPassword = !this.showPassword;
    };
    ProfileComponent.prototype.clickOnUploader = function (id) {
        $j('#' + id).click();
    };
    ProfileComponent.prototype.uploadSignatureTrigger = function (fileInput) {
        if (fileInput.target.files && fileInput.target.files[0]) {
            var reader = new FileReader();
            this.signatureModel.name = fileInput.target.files[0].name;
            this.signatureModel.size = fileInput.target.files[0].size;
            this.signatureModel.type = fileInput.target.files[0].type;
            if (this.signatureModel.label == "") {
                this.signatureModel.label = this.signatureModel.name;
            }
            reader.readAsDataURL(fileInput.target.files[0]);
            reader.onload = function (value) {
                window['angularProfileComponent'].componentAfterUpload(value.target.result);
            };
        }
    };
    ProfileComponent.prototype.displaySignatureEditionForm = function (index) {
        this.selectedSignature = index;
        this.selectedSignatureLabel = this.user.signatures[index].signature_label;
    };
    ProfileComponent.prototype.changeEmailSignature = function () {
        var index = $j("#emailSignaturesSelect").prop("selectedIndex");
        this.mailSignatureModel.selected = index;
        if (index > 0) {
            tinymce.get('emailSignature').setContent(this.user.emailSignatures[index - 1].html_body);
            this.mailSignatureModel.title = this.user.emailSignatures[index - 1].title;
        }
        else {
            tinymce.get('emailSignature').setContent("");
            this.mailSignatureModel.title = "";
        }
    };
    ProfileComponent.prototype.addBasketRedirection = function () {
        var _this = this;
        if (typeof this.basketsToRedirect[0] != 'undefined' && $j("#absenceUser")[0].value) {
            var redirectModel = [];
            console.log(this.basketsToRedirect);
            this.basketsToRedirect.forEach(function (value) {
                redirectModel.push({
                    "basketId": _this.user.baskets[value].basket_id,
                    "basketName": _this.user.baskets[value].basket_name,
                    "virtual": _this.user.baskets[value].is_virtual,
                    "basketOwner": _this.user.baskets[value].basket_owner,
                    "newUser": $j("#absenceUser")[0].value,
                });
            });
            this.http.post(this.coreUrl + 'rest/currentUser/baskets/absence', redirectModel)
                .map(function (res) { return res.json(); })
                .subscribe(function (data) {
                $j('#selectBasketAbsenceUser option').prop('selected', false);
                $j("#absenceUser")[0].value = "";
                _this.basketsToRedirect = [];
                _this.user.redirectedBaskets = data.redirectedBaskets;
                _this.user.baskets.forEach(function (value, index) {
                    _this.user.baskets[index]['disabled'] = false;
                    _this.user.redirectedBaskets.forEach(function (value2) {
                        if (value.basket_id == value2.basket_id && value.basket_owner == value2.basket_owner) {
                            _this.user.baskets[index]['disabled'] = true;
                        }
                    });
                });
            }, function (err) {
                _this.resultInfo = JSON.parse(err._body).errors;
                $j('#resultInfo').removeClass().addClass('alert alert-danger alert-dismissible');
                $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function () {
                    $j("#resultInfo").slideUp(500);
                });
            });
        }
        else {
            this.resultInfo = "Veuillez sélectionner au moins une corbeille et un utilisateur";
            $j('#resultInfo').removeClass().addClass('alert alert-danger alert-dismissible');
            $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function () {
                $j("#resultInfo").slideUp(500);
            });
        }
    };
    ProfileComponent.prototype.delBasketRedirection = function (basket) {
        var _this = this;
        this.http.delete(this.coreUrl + 'rest/currentUser/baskets/' + basket.basket_id + '/absence')
            .map(function (res) { return res.json(); })
            .subscribe(function (data) {
            _this.user.redirectedBaskets = data.redirectedBaskets;
            _this.user.baskets.forEach(function (value, index) {
                _this.user.baskets[index]['disabled'] = false;
                _this.user.redirectedBaskets.forEach(function (value2) {
                    if (value.basket_id == value2.basket_id && value.basket_owner == value2.basket_owner) {
                        _this.user.baskets[index]['disabled'] = true;
                    }
                });
            });
            _this.resultInfo = "Redirection supprimée";
            $j('#resultInfo').removeClass().addClass('alert alert-success alert-dismissible');
            $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function () {
                $j("#resultInfo").slideUp(500);
            });
        }, function (err) {
            _this.resultInfo = JSON.parse(err._body).errors;
            $j('#resultInfo').removeClass().addClass('alert alert-danger alert-dismissible');
            $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function () {
                $j("#resultInfo").slideUp(500);
            });
        });
    };
    ProfileComponent.prototype.updateBasketColor = function (i, y) {
        var _this = this;
        this.http.put(this.coreUrl + "rest/currentUser/groups/" + this.user.regroupedBaskets[i].groupId + "/baskets/" + this.user.regroupedBaskets[i].baskets[y].basket_id, { "color": this.user.regroupedBaskets[i].baskets[y].color })
            .map(function (res) { return res.json(); })
            .subscribe(function (data) {
            _this.user.regroupedBaskets = data.userBaskets;
        }, function (err) {
            _this.resultInfo = JSON.parse(err._body).errors;
            $j('#resultInfo').removeClass().addClass('alert alert-danger alert-dismissible');
            $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function () {
                $j("#resultInfo").slideUp(500);
            });
        });
    };
    ProfileComponent.prototype.activateAbsence = function () {
        var _this = this;
        var r = confirm('Voulez-vous vraiment activer votre absence ? Vous serez automatiquement déconnecté.');
        if (r) {
            this.http.put(this.coreUrl + 'rest/currentUser/absence', {})
                .map(function (res) { return res.json(); })
                .subscribe(function () {
                location.hash = "";
                location.search = "?display=true&page=logout&abs_mode";
            }, function (err) {
                _this.resultInfo = JSON.parse(err._body).errors;
                $j('#resultInfo').removeClass().addClass('alert alert-danger alert-dismissible');
                $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function () {
                    $j("#resultInfo").slideUp(500);
                });
            });
        }
    };
    ProfileComponent.prototype.updatePassword = function () {
        var _this = this;
        this.http.put(this.coreUrl + 'rest/currentUser/password', this.passwordModel)
            .map(function (res) { return res.json(); })
            .subscribe(function (data) {
            if (data.errors) {
                _this.resultInfo = data.errors;
                $j('#resultInfo').removeClass().addClass('alert alert-danger alert-dismissible');
                $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function () {
                    $j("#resultInfo").slideUp(500);
                });
            }
            else {
                _this.showPassword = false;
                _this.passwordModel = {
                    currentPassword: "",
                    newPassword: "",
                    reNewPassword: "",
                };
                _this.resultInfo = data.success;
                $j('#resultInfo').removeClass().addClass('alert alert-success alert-dismissible');
                //auto close
                $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function () {
                    $j("#resultInfo").slideUp(500);
                });
            }
        });
    };
    ProfileComponent.prototype.submitEmailSignature = function () {
        var _this = this;
        this.mailSignatureModel.htmlBody = tinymce.get('emailSignature').getContent();
        this.http.post(this.coreUrl + 'rest/currentUser/emailSignature', this.mailSignatureModel)
            .map(function (res) { return res.json(); })
            .subscribe(function (data) {
            if (data.errors) {
                _this.resultInfo = data.errors;
                $j('#resultInfo').removeClass().addClass('alert alert-danger alert-dismissible');
                $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function () {
                    $j("#resultInfo").slideUp(500);
                });
            }
            else {
                _this.user.emailSignatures = data.emailSignatures;
                _this.mailSignatureModel = {
                    selected: 0,
                    htmlBody: "",
                    title: "",
                };
                tinymce.get('emailSignature').setContent("");
                _this.resultInfo = data.success;
                $j('#resultInfo').removeClass().addClass('alert alert-success alert-dismissible');
                $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function () {
                    $j("#resultInfo").slideUp(500);
                });
            }
        });
    };
    ProfileComponent.prototype.updateEmailSignature = function () {
        var _this = this;
        this.mailSignatureModel.htmlBody = tinymce.get('emailSignature').getContent();
        var id = this.user.emailSignatures[this.mailSignatureModel.selected - 1].id;
        this.http.put(this.coreUrl + 'rest/currentUser/emailSignature/' + id, this.mailSignatureModel)
            .map(function (res) { return res.json(); })
            .subscribe(function (data) {
            if (data.errors) {
                _this.resultInfo = data.errors;
                $j('#resultInfo').removeClass().addClass('alert alert-danger alert-dismissible');
                $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function () {
                    $j("#resultInfo").slideUp(500);
                });
            }
            else {
                _this.user.emailSignatures[_this.mailSignatureModel.selected - 1].title = data.emailSignature.title;
                _this.user.emailSignatures[_this.mailSignatureModel.selected - 1].html_body = data.emailSignature.html_body;
                _this.resultInfo = data.success;
                $j('#resultInfo').removeClass().addClass('alert alert-success alert-dismissible');
                $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function () {
                    $j("#resultInfo").slideUp(500);
                });
            }
        });
    };
    ProfileComponent.prototype.deleteEmailSignature = function () {
        var _this = this;
        var r = confirm('Voulez-vous vraiment supprimer la signature de mail ?');
        if (r) {
            var id = this.user.emailSignatures[this.mailSignatureModel.selected - 1].id;
            this.http.delete(this.coreUrl + 'rest/currentUser/emailSignature/' + id)
                .map(function (res) { return res.json(); })
                .subscribe(function (data) {
                if (data.errors) {
                    _this.resultInfo = data.errors;
                    $j('#resultInfo').removeClass().addClass('alert alert-danger alert-dismissible');
                    $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function () {
                        $j("#resultInfo").slideUp(500);
                    });
                }
                else {
                    _this.user.emailSignatures = data.emailSignatures;
                    _this.mailSignatureModel = {
                        selected: 0,
                        htmlBody: "",
                        title: "",
                    };
                    tinymce.get('emailSignature').setContent("");
                    _this.resultInfo = data.success;
                    $j('#resultInfo').removeClass().addClass('alert alert-success alert-dismissible');
                    $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function () {
                        $j("#resultInfo").slideUp(500);
                    });
                }
            });
        }
    };
    ProfileComponent.prototype.submitSignature = function () {
        var _this = this;
        this.http.post(this.coreUrl + 'rest/currentUser/signature', this.signatureModel)
            .map(function (res) { return res.json(); })
            .subscribe(function (data) {
            if (data.errors) {
                _this.resultInfo = data.errors;
                $j('#resultInfo').removeClass().addClass('alert alert-danger alert-dismissible');
                $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function () {
                    $j("#resultInfo").slideUp(500);
                });
            }
            else {
                _this.user.signatures = data.signatures;
                _this.signatureModel = {
                    base64: "",
                    base64ForJs: "",
                    name: "",
                    type: "",
                    size: 0,
                    label: "",
                };
                _this.resultInfo = data.success;
                $j('#resultInfo').removeClass().addClass('alert alert-success alert-dismissible');
                $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function () {
                    $j("#resultInfo").slideUp(500);
                });
            }
        });
    };
    ProfileComponent.prototype.updateSignature = function () {
        var _this = this;
        var id = this.user.signatures[this.selectedSignature].id;
        this.http.put(this.coreUrl + 'rest/currentUser/signature/' + id, { "label": this.selectedSignatureLabel })
            .map(function (res) { return res.json(); })
            .subscribe(function (data) {
            if (data.errors) {
                _this.resultInfo = data.errors;
                $j('#resultInfo').removeClass().addClass('alert alert-danger alert-dismissible');
                $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function () {
                    $j("#resultInfo").slideUp(500);
                });
            }
            else {
                _this.user.signatures[_this.selectedSignature].signature_label = data.signature.signature_label;
                _this.selectedSignature = -1;
                _this.selectedSignatureLabel = "";
                _this.resultInfo = data.success;
                $j('#resultInfo').removeClass().addClass('alert alert-success alert-dismissible');
                $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function () {
                    $j("#resultInfo").slideUp(500);
                });
            }
        });
    };
    ProfileComponent.prototype.deleteSignature = function (id) {
        var _this = this;
        var r = confirm('Voulez-vous vraiment supprimer la signature ?');
        if (r) {
            this.http.delete(this.coreUrl + 'rest/currentUser/signature/' + id)
                .map(function (res) { return res.json(); })
                .subscribe(function (data) {
                if (data.errors) {
                    _this.resultInfo = data.errors;
                    $j('#resultInfo').removeClass().addClass('alert alert-danger alert-dismissible');
                    $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function () {
                        $j("#resultInfo").slideUp(500);
                    });
                }
                else {
                    _this.user.signatures = data.signatures;
                    _this.resultInfo = data.success;
                    $j('#resultInfo').removeClass().addClass('alert alert-success alert-dismissible');
                    $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function () {
                        $j("#resultInfo").slideUp(500);
                    });
                }
            });
        }
    };
    ProfileComponent.prototype.onSubmit = function () {
        var _this = this;
        this.http.put(this.coreUrl + 'rest/user/profile', this.user)
            .map(function (res) { return res.json(); })
            .subscribe(function (data) {
            if (data.errors) {
                _this.resultInfo = data.errors;
                $j('#resultInfo').removeClass().addClass('alert alert-danger alert-dismissible');
                $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function () {
                    $j("#resultInfo").slideUp(500);
                });
            }
            else {
                _this.resultInfo = data.success;
                $j('#resultInfo').removeClass().addClass('alert alert-success alert-dismissible');
                //auto close
                $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function () {
                    $j("#resultInfo").slideUp(500);
                });
            }
        }, function (error) {
            alert(error.statusText);
        });
    };
    ProfileComponent.prototype.absenceModal = function () {
        createModal(this.user.absence, 'modal_redirect', 'auto', '950px');
        autocomplete(this.user.countBasketsForAbsence, 'index.php?display=true&module=basket&page=autocomplete_users_list');
    };
    return ProfileComponent;
}());
ProfileComponent = __decorate([
    core_1.Component({
        templateUrl: angularGlobals.profileView,
        styleUrls: ['../../node_modules/bootstrap/dist/css/bootstrap.min.css', 'css/profile.component.css']
    }),
    __metadata("design:paramtypes", [http_1.Http, core_1.NgZone])
], ProfileComponent);
exports.ProfileComponent = ProfileComponent;
