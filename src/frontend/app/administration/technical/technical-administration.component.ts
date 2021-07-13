import { Component, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { LANG } from '../../translate.component';
import { MatBottomSheet } from '@angular/material';

declare function $j(selector: any): any;

declare const angularGlobals: any;


@Component({
    templateUrl: "technical-administration.component.html",
    styleUrls: ['technical-administration.component.scss'],
})
export class TechnicalAdministrationComponent implements OnInit {

    mobileQuery                     : MediaQueryList;

    coreUrl                         : string;
    lang                            : any       = LANG;
    loading                         : boolean   = false;

    technicalServices               : any[]     = [];


    constructor(public http: HttpClient, private router: Router, private bottomSheet: MatBottomSheet) {
        $j("link[href='merged_css.php']").remove();
    }

    ngOnInit(): void {
        this.coreUrl = angularGlobals.coreUrl;

        this.loading = true;

        // TO DO : change route for real technical services
        this.http.get(this.coreUrl + 'rest/administration')
            .subscribe((data: any) => {
                this.technicalServices = data.administrations.supervision;
                this.loading = false;
            }, () => {
                location.href = "index.php";
            });
    }

    goToSpecifiedAdministration(service: any): void {
        this.router.navigate([service.servicepage]);
        this.bottomSheet.dismiss();
    }
}
