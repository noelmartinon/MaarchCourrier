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
    templateUrl : angularGlobals["save-numeric-packageView"],
    styleUrls   : ['../../node_modules/bootstrap/dist/css/bootstrap.min.css', 'css/profile.component.css']
})
export class SaveNumericPackageComponent implements OnInit {

    coreUrl                     : string;

    numericPackage              : any       = {
        base64                  : "",
        name                    : "",
        type                    : "",
        size                    : 0,
        label                   : "",
    };

    resultInfo                  : string    = "";
    loading                     : boolean   = false;


    constructor(public http: Http, private zone: NgZone) {
        window['angularSaveNumericPackageComponent'] = {
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

    }

    updateBreadcrumb(applicationName: string) {
        if ($j('#ariane')[0]) {
            $j('#ariane')[0].innerHTML = "<a href='index.php?reinit=true'>" + applicationName + "</a> > Enregistrer un packet numérique";
        }
    }

    ngOnInit(): void {
        this.prepareProfile();
        this.updateBreadcrumb(angularGlobals.applicationName);
        this.coreUrl = angularGlobals.coreUrl;

        this.loading = false;

    }

    processAfterUpload(b64Content: any) {
        this.zone.run(() => this.resfreshUpload(b64Content));
    }

    resfreshUpload(b64Content: any) {
        this.numericPackage.base64 = b64Content.replace(/^data:.*?;base64,/, "");
    }

    uploadNumericPackage(fileInput: any) {
        if (fileInput.target.files && fileInput.target.files[0]) {
            var reader = new FileReader();

            this.numericPackage.name = fileInput.target.files[0].name;
            this.numericPackage.size = fileInput.target.files[0].size;
            this.numericPackage.type = fileInput.target.files[0].type;
            if (this.numericPackage.label == "") {
                this.numericPackage.label = this.numericPackage.name;
            }

            reader.readAsDataURL(fileInput.target.files[0]);

            reader.onload = function (value: any) {
                window['angularSaveNumericPackageComponent'].componentAfterUpload(value.target.result);
            };

        }
    }

    submitNumericPackage() {
        if(this.numericPackage.size != 0) {
            this.http.post(this.coreUrl + 'rest/saveNumericPackage', this.numericPackage)
                .map(res => res.json())
                .subscribe((data) => {
                    if (data.errors) {
                        this.resultInfo = data.errors;
                        $j('#resultInfo').removeClass().addClass('alert alert-danger alert-dismissible');
                        $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function(){
                            $j("#resultInfo").slideUp(500);
                        }); 
                    } else {
                        this.numericPackage  = {
                            base64                  : "",
                            name                    : "",
                            type                    : "",
                            size                    : 0,
                            label                   : "",
                        };
                        this.resultInfo = 'Paquet numérique correctement importé';
                        $j('#resultInfo').removeClass().addClass('alert alert-success alert-dismissible');
                        $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function(){
                            $j("#resultInfo").slideUp(500);
                        }); 
                    }
                });
        } else {
            this.numericPackage.name        = "";
            this.numericPackage.size        = 0;
            this.numericPackage.type        = "";
            this.numericPackage.base64      = "";

            this.resultInfo = "Aucun paquet numérique séléctionné";
            $j('#resultInfo').removeClass().addClass('alert alert-danger alert-dismissible');
            $j("#resultInfo").fadeTo(3000, 500).slideUp(500, function(){
                $j("#resultInfo").slideUp(500);
            });
        }
    }

}
