import { NgModule } from '@angular/core';

import { SharedModule } from '../app-common.module';

import { CriteriaToolComponent } from '../adv-search/criteria-tool/criteria-tool.component';
import { SearchAdvListComponent } from '../adv-search/list/search-adv-list.component';
import { DocumentViewerModule } from '../viewer/document-viewer.module';

import { ConfirmActionComponent } from './confirm-action/confirm-action.component';
import { DisabledBasketPersistenceActionComponent } from './disabled-basket-persistence-action/disabled-basket-persistence-action.component';
import { EnabledBasketPersistenceActionComponent } from './enabled-basket-persistence-action/enabled-basket-persistence-action.component';
import { ResMarkAsReadActionComponent } from './res-mark-as-read-action/res-mark-as-read-action.component';
import { CloseMailActionComponent } from './close-mail-action/close-mail-action.component';
import { RejectVisaBackToPrevousActionComponent } from './visa-reject-back-to-previous-action/reject-visa-back-to-previous-action.component';
import { ResetVisaActionComponent } from './visa-reset-action/reset-visa-action.component';
import { InterruptVisaActionComponent } from './visa-interrupt-action/interrupt-visa-action.component';
import { UpdateAcknowledgementSendDateActionComponent } from './update-acknowledgement-send-date-action/update-acknowledgement-send-date-action.component';
import { CreateAcknowledgementReceiptActionComponent } from './create-acknowledgement-receipt-action/create-acknowledgement-receipt-action.component';
import { CloseAndIndexActionComponent } from './close-and-index-action/close-and-index-action.component';
import { UpdateDepartureDateActionComponent } from './update-departure-date-action/update-departure-date-action.component';
import { SendExternalSignatoryBookActionComponent } from './send-external-signatory-book-action/send-external-signatory-book-action.component';
import { SendExternalNoteBookActionComponent } from './send-external-note-book-action/send-external-note-book-action.component';
import { XParaphComponent } from './send-external-signatory-book-action/x-paraph/x-paraph.component';
import { MaarchParaphComponent } from './send-external-signatory-book-action/maarch-paraph/maarch-paraph.component';
import { IParaphComponent } from './send-external-signatory-book-action/i-paraph/i-paraph.component';
import { IxbusParaphComponent } from './send-external-signatory-book-action/ixbus-paraph/ixbus-paraph.component';
import { FastParaphComponent } from './send-external-signatory-book-action/fast-paraph/fast-paraph.component';
import { ViewDocActionComponent } from './view-doc-action/view-doc-action.component';
import { RedirectActionComponent } from './redirect-action/redirect-action.component';
import { SendShippingActionComponent } from './send-shipping-action/send-shipping-action.component';
import { redirectInitiatorEntityActionComponent } from './redirect-initiator-entity-action/redirect-initiator-entity-action.component';
import { closeMailWithAttachmentsOrNotesActionComponent } from './close-mail-with-attachments-or-notes-action/close-mail-with-attachments-or-notes-action.component';
import { SendSignatureBookActionComponent } from './visa-send-signature-book-action/send-signature-book-action.component';
import { ContinueVisaCircuitActionComponent } from './visa-continue-circuit-action/continue-visa-circuit-action.component';
import { ContinueAvisCircuitActionComponent } from './avis-continue-circuit-action/continue-avis-circuit-action.component';
import { SendAvisWorkflowComponent } from './avis-workflow-send-action/send-avis-workflow-action.component';
import { SendAvisParallelComponent } from './avis-parallel-send-action/send-avis-parallel-action.component';
import { GiveAvisParallelActionComponent } from './avis-give-parallel-action/give-avis-parallel-action.component';
import { ValidateAvisParallelComponent } from './avis-parallel-validate-action/validate-avis-parallel-action.component';
import { ReconcileActionComponent } from './reconciliation-action/reconcile-action.component';
import { SendAlfrescoActionComponent } from './send-alfresco-action/send-alfresco-action.component';
import { SaveRegisteredMailActionComponent } from './save-registered-mail-action/save-registered-mail-action.component';
import { SaveAndIndexRegisteredMailActionComponent } from './save-and-index-registered-mail-action/save-and-index-registered-mail-action.component';
import { SaveAndPrintRegisteredMailActionComponent } from './save-and-print-registered-mail-action/save-and-print-registered-mail-action.component';
import { PrintRegisteredMailActionComponent } from './print-registered-mail-action/print-registered-mail-action.component';

import { InternationalizationModule } from '../../service/translate/internationalization.module';
import { TranslateService } from '@ngx-translate/core';

@NgModule({
    imports: [
        SharedModule,
        DocumentViewerModule,
        InternationalizationModule
    ],
    declarations: [
        CriteriaToolComponent,
        SearchAdvListComponent,
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
        SendAlfrescoActionComponent,
        SaveRegisteredMailActionComponent,
        SaveAndPrintRegisteredMailActionComponent,
        SaveAndIndexRegisteredMailActionComponent,
        PrintRegisteredMailActionComponent,
        ReconcileActionComponent,
    ],
    exports: [
        CriteriaToolComponent,
        SearchAdvListComponent,
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
        SendAlfrescoActionComponent,
        SaveRegisteredMailActionComponent,
        SaveAndPrintRegisteredMailActionComponent,
        SaveAndIndexRegisteredMailActionComponent,
        PrintRegisteredMailActionComponent,
        ReconcileActionComponent,
        DocumentViewerModule
    ],
    entryComponents: [
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
        SendAlfrescoActionComponent,
        SaveRegisteredMailActionComponent,
        SaveAndPrintRegisteredMailActionComponent,
        SaveAndIndexRegisteredMailActionComponent,
        PrintRegisteredMailActionComponent,
        ViewDocActionComponent,
        ReconcileActionComponent,
    ]
})
export class ActionsModule {
    constructor(translate: TranslateService) {
        translate.setDefaultLang('fr');
    }
}
