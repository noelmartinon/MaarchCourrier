import { NgModule }                             from '@angular/core';

import { SharedModule }                         from './app-common.module';

import { CustomSnackbarComponent }              from './notification.service';
import { ConfirmModalComponent }                from './confirmModal.component';
import { HeaderService }                        from '../service/header.service';
import { FiltersListService }                   from '../service/filtersList.service';

import { AppComponent }                         from './app.component';
import { AppRoutingModule }                     from './app-routing.module';
import { AdministrationModule }                 from './administration/administration.module';

import { ProfileComponent }                     from './profile.component';
import { AboutUsComponent }                     from './about-us.component';
import { HomeComponent }                        from './home/home.component';
import { MaarchParapheurListComponent }         from './home/maarch-parapheur/maarch-parapheur-list.component';
import { BasketListComponent }                  from './list/basket-list.component';
import { PasswordModificationComponent, InfoChangePasswordModalComponent, }        from './password-modification.component';
import { SignatureBookComponent, SafeUrlPipe }  from './signature-book.component';
import { SaveNumericPackageComponent }          from './save-numeric-package.component';
import { ActivateUserComponent }                from './activate-user.component';

import { ActionsListComponent }                 from './actions/actions-list.component';

/*ACTIONS PAGES */
import { ConfirmActionComponent }               from './actions/confirm-action/confirm-action.component';
import { DisabledBasketPersistenceActionComponent } from './actions/disabled-basket-persistence-action/disabled-basket-persistence-action.component';
import { EnabledBasketPersistenceActionComponent } from './actions/enabled-basket-persistence-action/enabled-basket-persistence-action.component';
import { ResMarkAsReadActionComponent } from './actions/res-mark-as-read-action/res-mark-as-read-action.component';
import { CloseMailActionComponent }             from './actions/close-mail-action/close-mail-action.component';
import { UpdateAcknowledgementSendDateActionComponent }             from './actions/update-acknowledgement-send-date-action/update-acknowledgement-send-date-action.component';
import { CreateAcknowledgementReceiptActionComponent }             from './actions/create-acknowledgement-receipt-action/create-acknowledgement-receipt-action.component';
import { CloseAndIndexActionComponent }             from './actions/close-and-index-action/close-and-index-action.component';
import { UpdateDepartureDateActionComponent }   from './actions/update-departure-date-action/update-departure-date-action.component';
import { SendExternalSignatoryBookActionComponent }   from './actions/send-external-signatory-book-action/send-external-signatory-book-action.component';
import { SendExternalNoteBookActionComponent }   from './actions/send-external-note-book-action/send-external-note-book-action.component';
import { XParaphComponent }                         from './actions/send-external-signatory-book-action/x-paraph/x-paraph.component';
import { MaarchParaphComponent }                         from './actions/send-external-signatory-book-action/maarch-paraph/maarch-paraph.component';
import { ProcessActionComponent }               from './actions/process-action/process-action.component';
import { ViewDocActionComponent }               from './actions/view-doc-action/view-doc-action.component';
import { RedirectActionComponent }               from './actions/redirect-action/redirect-action.component';
import { SendShippingActionComponent }               from './actions/send-shipping-action/send-shipping-action.component';

import { FiltersListComponent }                 from './list/filters/filters-list.component';
import { FiltersToolComponent }                 from './list/filters/filters-tool.component';
import { ToolsListComponent }                 from './list/tools/tools-list.component';
import { PanelListComponent }                 from './list/panel/panel-list.component';
import { SummarySheetComponent }                from './list/summarySheet/summary-sheet.component';
import { ExportComponent }                      from './list/export/export.component';

import { NoteEditorComponent }                  from './notes/note-editor.component';
import { NotesListComponent }                   from './notes/notes.component';
import { AttachmentsListComponent }             from './attachments/attachments-list.component';
import { DiffusionsListComponent }             from './diffusions/diffusions-list.component';
import { VisaWorkflowComponent }             from './visa/visa-workflow.component';
import { AvisWorkflowComponent }             from './avis/avis-workflow.component';
import { ContactsListComponent } from './contact/list/contacts-list.component';
import { ContactsListModalComponent } from './contact/list/modal/contacts-list-modal.component';



@NgModule({
    imports: [
        SharedModule,
        AdministrationModule,
        AppRoutingModule,
    ],
    declarations: [
        AppComponent,
        ProfileComponent,
        AboutUsComponent,
        HomeComponent,
        MaarchParapheurListComponent,
        BasketListComponent,
        PasswordModificationComponent,
        SignatureBookComponent,
        SafeUrlPipe,
        SaveNumericPackageComponent,
        CustomSnackbarComponent,
        ConfirmModalComponent,
        InfoChangePasswordModalComponent,
        ActivateUserComponent,
        NotesListComponent,
        NoteEditorComponent,
        AttachmentsListComponent,
        DiffusionsListComponent,
        VisaWorkflowComponent,
        AvisWorkflowComponent,
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
        UpdateDepartureDateActionComponent,
        SendExternalSignatoryBookActionComponent,
        SendExternalNoteBookActionComponent,
        XParaphComponent,
        MaarchParaphComponent,
        ProcessActionComponent,
        ViewDocActionComponent,
        RedirectActionComponent,
        SendShippingActionComponent,
        ActionsListComponent,
        ContactsListComponent,
        ContactsListModalComponent
    ],
    entryComponents: [
        CustomSnackbarComponent,
        ConfirmModalComponent,
        InfoChangePasswordModalComponent,
        AttachmentsListComponent,
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
        UpdateDepartureDateActionComponent,
        SendExternalSignatoryBookActionComponent,
        SendExternalNoteBookActionComponent,
        ProcessActionComponent,
        RedirectActionComponent,
        SendShippingActionComponent,
        ViewDocActionComponent,
        ContactsListModalComponent
    ],
    providers: [ HeaderService, FiltersListService ],
    bootstrap: [ AppComponent ]
})
export class AppModule { }
