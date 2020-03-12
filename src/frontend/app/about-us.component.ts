import { Component, OnInit } from '@angular/core';
import { LANG } from './translate.component';
import { HeaderService } from '../service/header.service';
import { AppService } from '../service/app.service';

declare function $j(selector: any): any;

declare var angularGlobals: any;

@Component({
    templateUrl: "about-us.component.html",
    styleUrls: ['profile.component.css'],
    providers: [AppService]
})
export class AboutUsComponent implements OnInit {

    applicationVersion: string;
    lang: any = LANG;

    loading: boolean = false;

    constructor(
        private headerService: HeaderService,
        public appService: AppService) {
        $j("link[href='merged_css.php']").remove();
    }

    ngOnInit(): void {
        this.headerService.setHeader(this.lang.aboutUs);

        this.applicationVersion = angularGlobals.applicationVersion;
        this.loading = false;
    }
}
