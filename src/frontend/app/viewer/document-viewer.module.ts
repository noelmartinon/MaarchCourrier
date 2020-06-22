import { NgModule } from '@angular/core';

import { SharedModule } from '../app-common.module';

import { DocumentViewerComponent } from './document-viewer.component';
import { EcplOnlyofficeViewerComponent } from '../../plugins/onlyoffice-api-js/onlyoffice-viewer.component';

import { PdfViewerModule } from 'ng2-pdf-viewer';

@NgModule({
    imports: [
        SharedModule,
        PdfViewerModule
    ],
    declarations: [
        DocumentViewerComponent,
        EcplOnlyofficeViewerComponent
    ],
    exports: [
        PdfViewerModule,
        EcplOnlyofficeViewerComponent,
        DocumentViewerComponent,
    ],
    entryComponents: [
    ]
})
export class DocumentViewerModule { }
