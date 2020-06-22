import { NgModule } from '@angular/core';

import { SharedModule } from './app-common.module';

import { SignatureBookRoutingModule } from './signature-book-routing.module';
import { SignatureBookComponent } from './signature-book.component';
import { NoteModule } from './notes/note.module';
import { DocumentViewerModule } from './viewer/document-viewer.module';

@NgModule({
    imports: [
        SharedModule,
        SignatureBookRoutingModule,
        NoteModule,
        DocumentViewerModule
    ],
    declarations: [SignatureBookComponent],
    entryComponents: [

    ]
})
export class SignatureBookModule { }
