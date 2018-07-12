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

@Component({
    selector: 'my-app',
    template: `<router-outlet></router-outlet>`,
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
    }
}
