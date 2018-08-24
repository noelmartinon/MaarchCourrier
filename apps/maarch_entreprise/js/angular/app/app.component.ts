import { Component, ViewEncapsulation } from '@angular/core';
import { DomSanitizer } from '@angular/platform-browser';
import { MatIconRegistry } from '@angular/material';
import { MAT_TOOLTIP_DEFAULT_OPTIONS, MatTooltipDefaultOptions } from '@angular/material';

/** Custom options the configure the tooltip's default show/hide delays. */
export const myCustomTooltipDefaults: MatTooltipDefaultOptions = {
    showDelay: 500,
    hideDelay: 0,
    touchendHideDelay: 0,
};

declare const angularGlobals: any;
declare function $j(selector: any): any;
@Component({
    selector: 'my-app',
    template: `<main-header></main-header><router-outlet></router-outlet>`,
    encapsulation: ViewEncapsulation.None,
    styleUrls: [
        '../../../../../node_modules/bootstrap/dist/css/bootstrap.min.css',
        '../../../css/maarch-material.css',
        '../../../css/engine.css',
        '../../../css/jstree-custom.min.css', //treejs module
        '../../../../../node_modules/ng2-dnd/bundles/style.css'

    ],
    viewProviders: [MatIconRegistry],
    providers: [
        {provide: MAT_TOOLTIP_DEFAULT_OPTIONS, useValue: myCustomTooltipDefaults}
    ],
})
export class AppComponent {
    constructor(iconReg: MatIconRegistry, sanitizer: DomSanitizer) {

        iconReg.addSvgIcon('maarchLogo', sanitizer.bypassSecurityTrustResourceUrl('static.php?filename=logo_white.svg')).addSvgIcon('maarchLogoOnly', sanitizer.bypassSecurityTrustResourceUrl('img/logo_only_white.svg'));
        if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) {
            angularGlobals.mobileMode = true;
        } else {
            angularGlobals.mobileMode = false;
        } 

        /*REMOVE AFTER FULL MAARCH V2*/
        $j('#header').remove();
        $j('#inner_content').remove();
        $j('#inner_content_contact').parent('div').remove(); 
        $j('#inner_content_contact').remove(); 
        $j('#menunav').hide();
        $j('#divList').remove();
        $j('#magicContactsTable').remove();
        $j('#manageBasketsOrderTable').remove();
        $j('#controlParamTechnicTable').remove();
        $j('#container').width("100%");
        if ($j('#content h1')[0] && $j('#content h1')[0] != $j('my-app h1')[0]) {
            $j('#content h1')[0].remove();
        }
    }
}
