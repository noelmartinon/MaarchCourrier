import { NgModule } from '@angular/core';

import { SharedModule } from '../app-common.module';

import { ProcessRoutingModule } from './process-routing.module';
import { ActionsModule } from '../actions/actions.module';

import { ProcessComponent } from './process.component';
import { IndexationComponent } from '../indexation/indexation.component';
import { LinkResourceModalComponent } from '../linkedResource/linkResourceModal/link-resource-modal.component';
import { HistoryWorkflowResumeComponent } from '../history/history-workflow-resume/history-workflow-resume.component';
import { NoteResumeComponent } from '../notes/note-resume/note-resume.component';
import { AttachmentsResumeComponent } from '../attachments/attachments-resume/attachments-resume.component';
import { MailResumeComponent } from '../mail/mail-resume/mail-resume.component';
import { SentResourceListComponent } from '../sentResource/sent-resource-list.component';
import { SentResourcePageComponent } from '../sentResource/sent-resource-page/sent-resource-page.component';
import { SentNumericPackagePageComponent } from '../sentResource/sent-numeric-package-page/sent-numeric-package-page.component';
import { ThesaurusModalComponent } from '../tag/indexing/thesaurus/thesaurus-modal.component';
import { SelectIndexingModelComponent } from '../indexation/select-indexing-model/select-indexing-model.component';
import { DocumentFormModule } from '../document-form.module';

@NgModule({
    imports: [
        SharedModule,
        ActionsModule,
        ProcessRoutingModule,
        DocumentFormModule,
    ],
    declarations: [
        ProcessComponent,
        IndexationComponent,
        LinkResourceModalComponent,
        HistoryWorkflowResumeComponent,
        NoteResumeComponent,
        AttachmentsResumeComponent,
        MailResumeComponent,
        SentResourceListComponent,
        SentResourcePageComponent,
        SentNumericPackagePageComponent,
        ThesaurusModalComponent,
        SelectIndexingModelComponent,
    ],
    exports: [
        SharedModule,
    ],
    entryComponents: [
        LinkResourceModalComponent,
        SentResourcePageComponent,
        SentNumericPackagePageComponent,
        ThesaurusModalComponent,
    ]
})
export class ProcessModule { }
