import { NgModule } from '@angular/core';

import { SharedModule } from '../app-common.module';

import { DocumentViewerComponent } from './document-viewer.component';
import { DocumentViewerModalComponent } from './modal/document-viewer-modal.component';

import { EcplOnlyofficeViewerComponent } from '../../plugins/onlyoffice-api-js/onlyoffice-viewer.component';

import { PdfViewerModule } from 'ng2-pdf-viewer';
import {CollaboraOnlineViewerComponent} from '../../plugins/collabora-online/collabora-online-viewer.component';

@NgModule({
    imports: [
        SharedModule,
        PdfViewerModule
    ],
    declarations: [
        DocumentViewerComponent,
        EcplOnlyofficeViewerComponent,
        DocumentViewerModalComponent,
        CollaboraOnlineViewerComponent
    ],
    exports: [
        PdfViewerModule,
        EcplOnlyofficeViewerComponent,
        DocumentViewerComponent,
        CollaboraOnlineViewerComponent
    ],
    entryComponents: [
        DocumentViewerModalComponent
    ]
})
export class DocumentViewerModule { }
