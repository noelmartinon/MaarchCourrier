import { NgModule } from '@angular/core';
import { SharedModule } from './app-common.module';

import { IndexingFormComponent } from './indexation/indexing-form/indexing-form.component';
import { TagInputComponent } from './tag/indexing/tag-input.component';
import { FolderInputComponent } from '../app/folder/indexing/folder-input.component';

import { InternationalizationModule } from '../service/translate/internationalization.module';
import { TranslateService } from '@ngx-translate/core';

@NgModule({
    imports: [
        SharedModule,
        InternationalizationModule
    ],
    declarations: [
        IndexingFormComponent,
        TagInputComponent,
        FolderInputComponent
    ],
    exports: [
        IndexingFormComponent,
        TagInputComponent,
        FolderInputComponent
    ],
    entryComponents: [
    ],
    providers: [

    ],
})
export class DocumentFormModule {
    constructor(translate: TranslateService) {
        translate.setDefaultLang('fr');
    }
}
