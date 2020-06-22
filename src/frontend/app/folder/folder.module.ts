import { NgModule } from '@angular/core';

import { SharedModule } from '../app-common.module';
import { AppListModule } from '../app-list.module';

import { FolderRoutingModule } from './folder-routing.module';

import { FolderDocumentListComponent } from './document-list/folder-document-list.component';


@NgModule({
    imports: [
        SharedModule,
        AppListModule,
        FolderRoutingModule
    ],
    declarations: [
        FolderDocumentListComponent,
    ],
    entryComponents: [

    ]
})
export class FolderModule { }
