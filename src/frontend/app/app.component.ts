import { Component, ViewEncapsulation, ViewChild } from '@angular/core';
import { DomSanitizer } from '@angular/platform-browser';
import { MatIconRegistry } from '@angular/material/icon';
import { MAT_TOOLTIP_DEFAULT_OPTIONS, MatTooltipDefaultOptions } from '@angular/material/tooltip';
import { HeaderService } from '../service/header.service';
import { AppService } from '../service/app.service';
import { MatSidenav } from '@angular/material/sidenav';

/** Custom options the configure the tooltip's default show/hide delays. */
export const myCustomTooltipDefaults: MatTooltipDefaultOptions = {
    showDelay: 500,
    hideDelay: 0,
    touchendHideDelay: 0,
};

declare function $j(selector: any): any;

@Component({
    selector: 'my-app',
    templateUrl: 'app.component.html',
    encapsulation: ViewEncapsulation.None,
    styleUrls: [
        '../../../node_modules/bootstrap/dist/css/bootstrap.min.css',
        '../css/maarch-material.css',
        '../css/engine.scss',
        '../css/engine_2.scss',
        '../../../node_modules/ng2-dnd/bundles/style.css'
    ],
    viewProviders: [MatIconRegistry],
    providers: [
        AppService,
        { provide: MAT_TOOLTIP_DEFAULT_OPTIONS, useValue: myCustomTooltipDefaults }
    ],
})
export class AppComponent {

    @ViewChild('snavLeft', { static: false }) snavLeft: MatSidenav;

    constructor(
        iconReg: MatIconRegistry,
        sanitizer: DomSanitizer,
        public appService: AppService,
        public headerService: HeaderService
    ) {

        iconReg.addSvgIcon('maarchLogo', sanitizer.bypassSecurityTrustResourceUrl('static.php?filename=logo_white.svg')).addSvgIcon('maarchLogoOnly', sanitizer.bypassSecurityTrustResourceUrl('img/logo_only_white.svg'));
        iconReg.addSvgIcon('maarchLogoFull', sanitizer.bypassSecurityTrustResourceUrl('static.php?filename=logo.svg')).addSvgIcon('maarchLogoOnlyDefault', sanitizer.bypassSecurityTrustResourceUrl('img/logo.svg'));

        //this.headerService.loadHeader();

        /*REMOVE AFTER FULL MAARCH V2*/
        $j('my-app').css({ "display": "block" });
        $j('#maarch_content').remove();
        $j('#loadingAngularContent').remove();
        $j('#header').remove();
        $j('#inner_content').remove();
        $j('#menunav').hide();
        $j('#divList').remove();
        $j('#container').css({
            "width": "100%",
            "min-width": "auto"
        });
        $j('#content').css({
            "min-width": "auto"
        });
        if ($j('#content h1')[0] && $j('#content h1')[0] != $j('my-app h1')[0]) {
            $j('#content h1')[0].remove();
        }
    }

    ngOnInit(): void {

        this.headerService.hideSideBar = true;
        
        setTimeout(() => {
            this.headerService.sideNavLeft = this.snavLeft;
        }, 0);

        this.headerService.sideNavLeft = this.snavLeft;
    }
}
