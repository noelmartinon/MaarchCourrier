import { NgModule } from '@angular/core';

import { SharedModule } from './app-common.module';

import { BrowserModule, HammerGestureConfig, HAMMER_GESTURE_CONFIG } from '@angular/platform-browser';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';

import { HttpClientModule, HTTP_INTERCEPTORS } from '@angular/common/http';

import { AuthInterceptor } from '../service/auth-interceptor.service';
import { FiltersListService } from '../service/filtersList.service';
import { FoldersService } from './folder/folders.service';
import { PrivilegeService } from '../service/privileges.service';
import { ActionsService } from './actions/actions.service';

import { AppComponent } from './app.component';
import { AppRoutingModule } from './app-routing.module';

import { LoginComponent } from './login/login.component';
import { ProfileComponent } from './profile.component';

import { AboutUsComponent } from './about-us.component';
import { HomeComponent } from './home/home.component';
import { MaarchParapheurListComponent } from './home/maarch-parapheur/maarch-parapheur-list.component';

import { PasswordModificationComponent, InfoChangePasswordModalComponent, } from './login/passwordModification/password-modification.component';
import { SaveNumericPackageComponent } from './save-numeric-package.component';
import { ActivateUserComponent } from './activate-user.component';

import { FolderPinnedComponent } from './folder/folder-pinned/folder-pinned.component';
import { FolderTreeComponent } from './folder/folder-tree.component';
import { PanelFolderComponent } from './folder/panel/panel-folder.component';
import { FolderUpdateComponent } from './folder/folder-update/folder-update.component';

import { DocumentViewerModule } from './viewer/document-viewer.module';
import { PrintSeparatorComponent } from './separator/print-separator/print-separator.component';

import { ForgotPasswordComponent } from './login/forgotPassword/forgotPassword.component';
import { ResetPasswordComponent } from './login/resetPassword/reset-password.component';

import { AttachmentPageComponent } from './attachments/attachments-page/attachment-page.component';
import { AttachmentCreateComponent } from './attachments/attachment-create/attachment-create.component';

import { AddPrivateIndexingModelModalComponent } from './indexation/private-indexing-model/add-private-indexing-model-modal.component';

import { ContactResourceModalComponent } from './contact/contact-resource/modal/contact-resource-modal.component';
import { ContactModalComponent } from './administration/contact/modal/contact-modal.component';

import { VisaWorkflowModalComponent } from './visa/modal/visa-workflow-modal.component';
import { AddVisaModelModalComponent } from './visa/addVisaModel/add-visa-model-modal.component';
import { AddAvisModelModalComponent } from './avis/addAvisModel/add-avis-model-modal.component';

import { DocumentViewerModalComponent } from './viewer/modal/document-viewer-modal.component';

import { FolderCreateModalComponent } from './folder/folder-create-modal/folder-create-modal.component';
import { DocumentViewerPageComponent } from './viewer/page/document-viewer-page.component';

import { RedirectIndexingModelComponent } from './administration/indexingModel/redirectIndexingModel/redirect-indexing-model.component';

export class MyHammerConfig extends HammerGestureConfig {
    overrides = <any>{
        'pinch': { enable: false },
        'rotate': { enable: false }
    };
}

@NgModule({
    imports: [
        BrowserModule,
        BrowserAnimationsModule,
        HttpClientModule,
        SharedModule,
        AppRoutingModule,
        DocumentViewerModule
    ],
    declarations: [
        AppComponent,
        LoginComponent,
        ProfileComponent,
        AboutUsComponent,
        HomeComponent,
        MaarchParapheurListComponent,
        PasswordModificationComponent,
        SaveNumericPackageComponent,
        InfoChangePasswordModalComponent,
        ActivateUserComponent,
        PrintSeparatorComponent,
        FolderPinnedComponent,
        FolderTreeComponent,
        PanelFolderComponent,
        FolderUpdateComponent,
        ForgotPasswordComponent,
        ResetPasswordComponent,
        AttachmentPageComponent,
        AttachmentCreateComponent,
        AddPrivateIndexingModelModalComponent,
        ContactResourceModalComponent,
        ContactModalComponent,
        VisaWorkflowModalComponent,
        AddVisaModelModalComponent,
        AddAvisModelModalComponent,
        DocumentViewerModalComponent,
        FolderCreateModalComponent,
        DocumentViewerPageComponent,
        RedirectIndexingModelComponent,
    ],
    entryComponents: [
        InfoChangePasswordModalComponent,
        FolderUpdateComponent,
        AddPrivateIndexingModelModalComponent,
        AttachmentPageComponent,
        AttachmentCreateComponent,
        ContactResourceModalComponent,
        ContactModalComponent,
        VisaWorkflowModalComponent,
        AddVisaModelModalComponent,
        AddAvisModelModalComponent,
        DocumentViewerModalComponent,
        FolderCreateModalComponent,
        RedirectIndexingModelComponent,
    ],
    providers: [{ provide: HTTP_INTERCEPTORS, useClass: AuthInterceptor, multi: true }, FiltersListService, FoldersService, ActionsService, PrivilegeService, {
        provide: HAMMER_GESTURE_CONFIG,
        useClass: MyHammerConfig
    }],
    bootstrap: [AppComponent]
})
export class AppModule { }
