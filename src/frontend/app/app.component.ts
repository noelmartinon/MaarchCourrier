import { Component, ViewChild, OnInit } from '@angular/core';
import { DomSanitizer } from '@angular/platform-browser';
import { MatIconRegistry } from '@angular/material/icon';
import { MAT_TOOLTIP_DEFAULT_OPTIONS, MatTooltipDefaultOptions } from '@angular/material/tooltip';
import { HeaderService } from '../service/header.service';
import { AppService } from '../service/app.service';
import { MatSidenav } from '@angular/material/sidenav';
import { LangService } from '../service/app-lang.service';
import { HttpClient } from '@angular/common/http';
import { AuthService } from '../service/auth.service';
import { environment } from '../environments/environment';

/** Custom options the configure the tooltip's default show/hide delays. */
export const myCustomTooltipDefaults: MatTooltipDefaultOptions = {
    showDelay: 500,
    hideDelay: 0,
    touchendHideDelay: 0,
};

@Component({
    selector: 'app-root',
    templateUrl: 'app.component.html',
    viewProviders: [MatIconRegistry],
    providers: [
        AppService,
        { provide: MAT_TOOLTIP_DEFAULT_OPTIONS, useValue: myCustomTooltipDefaults }
    ],
})
export class AppComponent implements OnInit {
    debugMode: boolean = false;
    @ViewChild('snavLeft', { static: true }) snavLeft: MatSidenav;

    constructor(
        public http: HttpClient,
        public langService: LangService,
        iconReg: MatIconRegistry,
        sanitizer: DomSanitizer,
        public appService: AppService,
        public headerService: HeaderService,
        public authService: AuthService,
    ) {
        iconReg.addSvgIcon('maarchLogo', sanitizer.bypassSecurityTrustResourceUrl('../rest/images?image=onlyLogo'));
        iconReg.addSvgIcon('maarchLogoFull', sanitizer.bypassSecurityTrustResourceUrl('../rest/images?image=logo'));
        iconReg.addSvgIcon('maarchLogoWhite', sanitizer.bypassSecurityTrustResourceUrl('assets/logo_only_white.svg'));
        iconReg.addSvgIcon('maarchLogoWhiteFull', sanitizer.bypassSecurityTrustResourceUrl('assets/logo_white.svg'));
        iconReg.addSvgIcon('maarchBox', sanitizer.bypassSecurityTrustResourceUrl('assets/maarch_box.svg'));
        iconReg.addSvgIcon('alfresco', sanitizer.bypassSecurityTrustResourceUrl('assets/alfresco.svg'));
        if (!environment.production) {
            this.debugMode = true;
        }
    }

    ngOnInit() {
        this.headerService.hideSideBar = true;
        this.headerService.sideNavLeft = this.snavLeft;
    }
}
