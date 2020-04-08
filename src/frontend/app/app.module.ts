import { NgModule }                             from '@angular/core';

import { SharedModule }                         from './app-common.module';

import { FiltersListService }                   from '../service/filtersList.service';

import { AppComponent }                         from './app.component';
import { AppRoutingModule }                     from './app-routing.module';
import { AdministrationModule }                 from './administration/administration.module';

import { LoginComponent }                       from './login/login.component';
import { ProfileComponent }                     from './profile.component';
import { AboutUsComponent }                     from './about-us.component';
import { HomeComponent }                        from './home/home.component';
import { MaarchParapheurListComponent }         from './home/maarch-parapheur/maarch-parapheur-list.component';
import { BasketListComponent }                  from './list/basket-list.component';
import { ProcessComponent }                  from './process/process.component';

import { PasswordModificationComponent, InfoChangePasswordModalComponent, }        from './password-modification.component';
import { SignatureBookComponent, SafeUrlPipe }  from './signature-book.component';
import { SaveNumericPackageComponent }          from './save-numeric-package.component';
import { ActivateUserComponent }                from './activate-user.component';

import { ActionsListComponent }                 from './actions/actions-list.component';

import { FolderPinnedComponent }                 from './folder/folder-pinned/folder-pinned.component';
import { FolderTreeComponent }                 from './folder/folder-tree.component';
import { FolderDocumentListComponent }                 from './folder/document-list/folder-document-list.component';
import { PanelFolderComponent }                 from './folder/panel/panel-folder.component';
import { FolderMenuComponent }                 from './folder/folder-menu/folder-menu.component';
import { FolderUpdateComponent }                 from './folder/folder-update/folder-update.component';
import { FolderActionListComponent }                 from './folder/folder-action-list/folder-action-list.component';

import { FollowedDocumentListComponent }                 from './home/followed-list/followed-document-list.component';
import { FollowedActionListComponent }                 from './home/followed-action-list/followed-action-list.component';

import { PrintedFolderModalComponent }                 from './printedFolder/printed-folder-modal.component';

/*ACTIONS PAGES */
import { ConfirmActionComponent }               from './actions/confirm-action/confirm-action.component';
import { DisabledBasketPersistenceActionComponent } from './actions/disabled-basket-persistence-action/disabled-basket-persistence-action.component';
import { EnabledBasketPersistenceActionComponent } from './actions/enabled-basket-persistence-action/enabled-basket-persistence-action.component';
import { ResMarkAsReadActionComponent } from './actions/res-mark-as-read-action/res-mark-as-read-action.component';
import { CloseMailActionComponent }             from './actions/close-mail-action/close-mail-action.component';
import { RejectVisaBackToPrevousActionComponent } from './actions/visa-reject-back-to-previous-action/reject-visa-back-to-previous-action.component';
import { ResetVisaActionComponent } from './actions/visa-reset-action/reset-visa-action.component';
import { InterruptVisaActionComponent } from './actions/visa-interrupt-action/interrupt-visa-action.component';
import { UpdateAcknowledgementSendDateActionComponent }             from './actions/update-acknowledgement-send-date-action/update-acknowledgement-send-date-action.component';
import { CreateAcknowledgementReceiptActionComponent }             from './actions/create-acknowledgement-receipt-action/create-acknowledgement-receipt-action.component';
import { CloseAndIndexActionComponent }             from './actions/close-and-index-action/close-and-index-action.component';
import { UpdateDepartureDateActionComponent }   from './actions/update-departure-date-action/update-departure-date-action.component';
import { SendExternalSignatoryBookActionComponent }   from './actions/send-external-signatory-book-action/send-external-signatory-book-action.component';
import { SendExternalNoteBookActionComponent }   from './actions/send-external-note-book-action/send-external-note-book-action.component';
import { XParaphComponent }                         from './actions/send-external-signatory-book-action/x-paraph/x-paraph.component';
import { MaarchParaphComponent }                         from './actions/send-external-signatory-book-action/maarch-paraph/maarch-paraph.component';
import { IParaphComponent }                  from './actions/send-external-signatory-book-action/i-paraph/i-paraph.component';
import { IxbusParaphComponent }                  from './actions/send-external-signatory-book-action/ixbus-paraph/ixbus-paraph.component';
import { FastParaphComponent }                  from './actions/send-external-signatory-book-action/fast-paraph/fast-paraph.component';
import { ViewDocActionComponent }               from './actions/view-doc-action/view-doc-action.component';
import { RedirectActionComponent }               from './actions/redirect-action/redirect-action.component';
import { SendShippingActionComponent }               from './actions/send-shipping-action/send-shipping-action.component';
import { redirectInitiatorEntityActionComponent } from './actions/redirect-initiator-entity-action/redirect-initiator-entity-action.component';
import { closeMailWithAttachmentsOrNotesActionComponent } from './actions/close-mail-with-attachments-or-notes-action/close-mail-with-attachments-or-notes-action.component';
import { SendSignatureBookActionComponent }               from './actions/visa-send-signature-book-action/send-signature-book-action.component';
import { ContinueVisaCircuitActionComponent }               from './actions/visa-continue-circuit-action/continue-visa-circuit-action.component';
import { ContinueAvisCircuitActionComponent }               from './actions/avis-continue-circuit-action/continue-avis-circuit-action.component';
import { SendAvisWorkflowComponent }               from './actions/avis-workflow-send-action/send-avis-workflow-action.component';
import { SendAvisParallelComponent }               from './actions/avis-parallel-send-action/send-avis-parallel-action.component';
import { GiveAvisParallelActionComponent }               from './actions/avis-give-parallel-action/give-avis-parallel-action.component';
import { ValidateAvisParallelComponent }               from './actions/avis-parallel-validate-action/validate-avis-parallel-action.component';
import { ReconcileActionComponent }               from './actions/reconciliation-action/reconcile-action.component';

import { FiltersListComponent }                 from './list/filters/filters-list.component';
import { FiltersToolComponent }                 from './list/filters/filters-tool.component';
import { ToolsListComponent }                 from './list/tools/tools-list.component';
import { PanelListComponent }                 from './list/panel/panel-list.component';
import { SummarySheetComponent }                from './list/summarySheet/summary-sheet.component';
import { ExportComponent }                      from './list/export/export.component';

import { NoteEditorComponent }                  from './notes/note-editor.component';
import { NotesListComponent }                   from './notes/notes.component';
import { AttachmentsListComponent }             from './attachments/attachments-list.component';

import { PrintSeparatorComponent }                        from './separator/print-separator/print-separator.component';

import { IndexationComponent }                        from './indexation/indexation.component';
import { ForgotPasswordComponent }          from './login/forgotPassword/forgotPassword.component';
import { UpdatePasswordComponent }          from './login/updatePassword/updatePassword.component';
import { HistoryWorkflowResumeComponent }                        from './history/history-workflow-resume/history-workflow-resume.component';
import { NoteResumeComponent }                        from './notes/note-resume/note-resume.component';
import { AttachmentsResumeComponent }                        from './attachments/attachments-resume/attachments-resume.component';
import { AttachmentPageComponent }                        from './attachments/attachments-page/attachment-page.component';
import { AttachmentCreateComponent }                        from './attachments/attachment-create/attachment-create.component';
import { MailResumeComponent }                        from './mail/mail-resume/mail-resume.component';
import { AddPrivateIndexingModelModalComponent }                        from './indexation/private-indexing-model/add-private-indexing-model-modal.component';
import { FoldersService } from './folder/folders.service';
import { PrivilegeService } from '../service/privileges.service';
import { ActionsService } from './actions/actions.service';
import { ContactsListComponent } from './contact/list/contacts-list.component';
import { ContactsListModalComponent } from './contact/list/modal/contacts-list-modal.component';
import { ContactModalComponent } from './administration/contact/modal/contact-modal.component';
import { VisaWorkflowModalComponent } from './visa/modal/visa-workflow-modal.component';
import { AddVisaModelModalComponent } from './visa/addVisaModel/add-visa-model-modal.component';
import { AddAvisModelModalComponent } from './avis/addAvisModel/add-avis-model-modal.component';

import { CriteriaToolComponent } from './adv-search/criteria-tool/criteria-tool.component';
import { SearchAdvListComponent } from './adv-search/list/search-adv-list.component';
import { LinkedResourceListComponent } from './linkedResource/linked-resource-list.component';
import { LinkResourceModalComponent } from './linkedResource/linkResourceModal/link-resource-modal.component';

import { DocumentViewerModalComponent } from './viewer/modal/document-viewer-modal.component';
import { SentResourceListComponent } from './sentResource/sent-resource-list.component';
import { SentResourcePageComponent } from './sentResource/sent-resource-page/sent-resource-page.component';
import { SentNumericPackagePageComponent } from './sentResource/sent-numeric-package-page/sent-numeric-package-page.component';
import { FolderCreateModalComponent } from './folder/folder-create-modal/folder-create-modal.component';



@NgModule({
    imports: [
        SharedModule,
        AdministrationModule,
        AppRoutingModule,
    ],
    declarations: [
        AppComponent,
        LoginComponent,
        ProfileComponent,
        AboutUsComponent,
        HomeComponent,
        MaarchParapheurListComponent,
        BasketListComponent,
        ProcessComponent,
        PasswordModificationComponent,
        SignatureBookComponent,
        SafeUrlPipe,
        SaveNumericPackageComponent,
        InfoChangePasswordModalComponent,
        ActivateUserComponent,
        NotesListComponent,
        NoteEditorComponent,
        AttachmentsListComponent,
        FiltersListComponent,
        FiltersToolComponent,
        ToolsListComponent,
        PanelListComponent,
        SummarySheetComponent,
        ExportComponent,
        ConfirmActionComponent,
        ResMarkAsReadActionComponent,
        EnabledBasketPersistenceActionComponent,
        DisabledBasketPersistenceActionComponent,
        CloseAndIndexActionComponent,
        UpdateAcknowledgementSendDateActionComponent,
        CreateAcknowledgementReceiptActionComponent,
        CloseMailActionComponent,
        RejectVisaBackToPrevousActionComponent,
        ResetVisaActionComponent,
        InterruptVisaActionComponent,
        UpdateDepartureDateActionComponent,
        SendExternalSignatoryBookActionComponent,
        SendExternalNoteBookActionComponent,
        XParaphComponent,
        MaarchParaphComponent,
        FastParaphComponent,
        IxbusParaphComponent,
        IParaphComponent,
        ViewDocActionComponent,
        RedirectActionComponent,
        SendShippingActionComponent,
        redirectInitiatorEntityActionComponent,
        closeMailWithAttachmentsOrNotesActionComponent,
        SendSignatureBookActionComponent,
        ContinueVisaCircuitActionComponent,
        ContinueAvisCircuitActionComponent,
        SendAvisWorkflowComponent,
        SendAvisParallelComponent,
        GiveAvisParallelActionComponent,
        ValidateAvisParallelComponent,
        ActionsListComponent,
        PrintSeparatorComponent,
        FolderPinnedComponent,
        FolderTreeComponent,
        PanelFolderComponent,
        FolderDocumentListComponent,
        FolderMenuComponent,
        FolderUpdateComponent,
        FolderActionListComponent,
        IndexationComponent,
        ForgotPasswordComponent,
        UpdatePasswordComponent,
        HistoryWorkflowResumeComponent,
        NoteResumeComponent,
        AttachmentsResumeComponent,
        AttachmentPageComponent,
        AttachmentCreateComponent,
        AddPrivateIndexingModelModalComponent,
        MailResumeComponent,
        ContactsListComponent,
        ContactsListModalComponent,
        ContactModalComponent,
        FollowedDocumentListComponent,
        FollowedActionListComponent,
        VisaWorkflowModalComponent,
        AddVisaModelModalComponent,
        AddAvisModelModalComponent,
        CriteriaToolComponent,
        SearchAdvListComponent,
        LinkedResourceListComponent,
        LinkResourceModalComponent,
        DocumentViewerModalComponent,
        SentResourceListComponent,
        SentResourcePageComponent,
        PrintedFolderModalComponent,
        SentNumericPackagePageComponent,
        FolderCreateModalComponent,
        ReconcileActionComponent
    ],
    entryComponents: [
        InfoChangePasswordModalComponent,
        SummarySheetComponent,
        ExportComponent,
        ConfirmActionComponent,
        ResMarkAsReadActionComponent,
        EnabledBasketPersistenceActionComponent,
        DisabledBasketPersistenceActionComponent,
        CloseAndIndexActionComponent,
        UpdateAcknowledgementSendDateActionComponent,
        CreateAcknowledgementReceiptActionComponent,
        CloseMailActionComponent,
        RejectVisaBackToPrevousActionComponent,
        ResetVisaActionComponent,
        InterruptVisaActionComponent,
        SendAvisWorkflowComponent,
        SendAvisParallelComponent,
        UpdateDepartureDateActionComponent,
        SendExternalSignatoryBookActionComponent,
        SendExternalNoteBookActionComponent,
        ContinueVisaCircuitActionComponent,
        ContinueAvisCircuitActionComponent,
        GiveAvisParallelActionComponent,
        ValidateAvisParallelComponent,
        RedirectActionComponent,
        SendShippingActionComponent,
        redirectInitiatorEntityActionComponent,
        closeMailWithAttachmentsOrNotesActionComponent,
        SendSignatureBookActionComponent,
        ViewDocActionComponent,
        FolderUpdateComponent,
        AddPrivateIndexingModelModalComponent,
        AttachmentPageComponent,
        AttachmentCreateComponent,
        ContactsListModalComponent,
        ContactModalComponent,
        VisaWorkflowModalComponent,
        AddVisaModelModalComponent,
        AddAvisModelModalComponent,
        LinkResourceModalComponent,
        DocumentViewerModalComponent,
        SentResourcePageComponent,
        PrintedFolderModalComponent,
        SentNumericPackagePageComponent,
        FolderCreateModalComponent,
        ReconcileActionComponent
    ],
    providers: [ FiltersListService, FoldersService, ActionsService, PrivilegeService ],
    bootstrap: [ AppComponent ]
})
export class AppModule { }
