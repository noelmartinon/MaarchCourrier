import { NgModule } from '@angular/core';

import { SharedModule } from '../../app-common.module';
import { AppListModule } from '../../app-list.module';

import { FollowedDcumentRoutingModule } from './followed-document-routing.module';

import { FollowedDocumentListComponent } from './followed-document-list.component';

@NgModule({
    imports: [
        SharedModule,
        AppListModule,
        FollowedDcumentRoutingModule
    ],
    declarations: [
        FollowedDocumentListComponent
    ],
    entryComponents: [

    ]
})
export class FollowedDocumentModule { }
