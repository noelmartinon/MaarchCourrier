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
var SaveNumericPackageComponent = (function () {
    function SaveNumericPackageComponent(http, zone) {
        var _this = this;
        this.http = http;
        this.zone = zone;
        this.numericPackage = {
            base64: "",
            name: "",
            type: "",
            size: 0,
            label: "",
            extension: "",
        };
        this.resultInfo = "";
        this.loading = false;
        window['angularSaveNumericPackageComponent'] = {
            componentAfterUpload: function (base64Content) { return _this.processAfterUpload(base64Content); },
        };
    }
    SaveNumericPackageComponent.prototype.preparePage = function () {
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
    };
    SaveNumericPackageComponent.prototype.updateBreadcrumb = function (applicationName) {
        if ($j('#ariane')[0]) {
            $j('#ariane')[0].innerHTML = "<a href='index.php?reinit=true'>" + applicationName + "</a> > Enregistrer un packet numérique";
        }
    };
    SaveNumericPackageComponent.prototype.ngOnInit = function () {
        this.preparePage();
        this.updateBreadcrumb(angularGlobals.applicationName);
        this.coreUrl = angularGlobals.coreUrl;
        this.loading = false;
    };
    SaveNumericPackageComponent.prototype.processAfterUpload = function (b64Content) {
        var _this = this;
        this.zone.run(function () { return _this.resfreshUpload(b64Content); });
    };
    SaveNumericPackageComponent.prototype.resfreshUpload = function (b64Content) {
        this.numericPackage.base64 = b64Content.replace(/^data:.*?;base64,/, "");
    };
    SaveNumericPackageComponent.prototype.uploadNumericPackage = function (fileInput) {
        if (fileInput.target.files && fileInput.target.files[0]) {
            var reader = new FileReader();
            this.numericPackage.name = fileInput.target.files[0].name;
            this.numericPackage.size = fileInput.target.files[0].size;
            this.numericPackage.type = fileInput.target.files[0].type;
            this.numericPackage.extension = fileInput.target.files[0].name.split('.').pop();
            if (this.numericPackage.label == "") {
                this.numericPackage.label = this.numericPackage.name;
            }
            reader.readAsDataURL(fileInput.target.files[0]);
            reader.onload = function (value) {
                window['angularSaveNumericPackageComponent'].componentAfterUpload(value.target.result);
            };
        }
    };
    SaveNumericPackageComponent.prototype.submitNumericPackage = function () {
        var _this = this;
        if (this.numericPackage.size != 0) {
            this.http.post(this.coreUrl + 'rest/saveNumericPackage', this.numericPackage)
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
                    _this.numericPackage = {
                        base64: "",
                        name: "",
                        type: "",
                        size: 0,
                        label: "",
                        extension: "",
                    };
                    $j("#numericPackageFilePath").val(null);
                    _this.resultInfo = 'Paquet numérique correctement importé';
                    $j('#resultInfo').removeClass().addClass('alert alert-success alert-dismissible');
                    $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function () {
                        $j("#resultInfo").slideUp(500);
                    });
                }
            });
        }
        else {
            this.numericPackage.name = "";
            this.numericPackage.size = 0;
            this.numericPackage.type = "";
            this.numericPackage.base64 = "";
            this.numericPackage.extension = "";
            this.resultInfo = "Aucun paquet numérique séléctionné";
            $j('#resultInfo').removeClass().addClass('alert alert-danger alert-dismissible');
            $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function () {
                $j("#resultInfo").slideUp(500);
            });
        }
    };
    return SaveNumericPackageComponent;
}());
SaveNumericPackageComponent = __decorate([
    core_1.Component({
        templateUrl: angularGlobals["save-numeric-packageView"],
        styleUrls: ['../../node_modules/bootstrap/dist/css/bootstrap.min.css', 'css/profile.component.css']
    }),
    __metadata("design:paramtypes", [http_1.Http, core_1.NgZone])
], SaveNumericPackageComponent);
exports.SaveNumericPackageComponent = SaveNumericPackageComponent;
