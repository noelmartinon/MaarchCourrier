import { NgModule } from '@angular/core';
import { RouterModule, Routes, PreloadAllModules } from '@angular/router';
import { AppGuard } from '../service/app.guard';

import { ActivateUserComponent } from './activate-user.component';
import { PasswordModificationComponent } from './login/passwordModification/password-modification.component';
import { ProfileComponent } from './profile.component';
import { AboutUsComponent } from './about-us.component';
import { HomeComponent } from './home/home.component';
import { SaveNumericPackageComponent } from './save-numeric-package.component';
import { PrintSeparatorComponent } from './separator/print-separator/print-separator.component';
import { ForgotPasswordComponent } from './login/forgotPassword/forgotPassword.component';
import { ResetPasswordComponent } from './login/resetPassword/reset-password.component';
import { DocumentViewerPageComponent } from './viewer/page/document-viewer-page.component';
import { LoginComponent } from './login/login.component';
import { SignatureBookComponent } from './signature-book.component';
import { FollowedDocumentListComponent } from './home/followed-list/followed-document-list.component';
import { FolderDocumentListComponent } from './folder/document-list/folder-document-list.component';
import { BasketListComponent } from './list/basket-list.component';
import { AcknowledgementReceptionComponent } from './registeredMails/acknowledgement-reception/acknowledgement-reception.component';


const routes: Routes = [
    { path: 'resources/:resId/content', canActivate: [AppGuard], component: DocumentViewerPageComponent },
    {
        path: 'install',
        loadChildren: () => import('./installer/installer.module').then(m => m.InstallerModule)
    },
    {
        path: 'administration',
        loadChildren: () => import('./administration/administration.module').then(m => m.AdministrationModule)
    },
    { path: 'signatureBook/users/:userId/groups/:groupId/baskets/:basketId/resources/:resId', canActivate: [AppGuard], component: SignatureBookComponent },
    { path: 'followed', canActivate: [AppGuard], component: FollowedDocumentListComponent },
    { path: 'saveNumericPackage', canActivate: [AppGuard], component: SaveNumericPackageComponent },
    { path: 'separators/print', canActivate: [AppGuard], component: PrintSeparatorComponent },
    { path: 'forgot-password', component: ForgotPasswordComponent },
    { path: 'reset-password', component: ResetPasswordComponent },
    { path: 'activate-user', component: ActivateUserComponent },
    { path: 'password-modification', component: PasswordModificationComponent },
    { path: 'folders/:folderId', canActivate: [AppGuard], component: FolderDocumentListComponent },
    { path: 'profile', canActivate: [AppGuard], component: ProfileComponent },
    { path: 'about-us', canActivate: [AppGuard], component: AboutUsComponent },
    { path: 'home', canActivate: [AppGuard], component: HomeComponent },
    { path: 'basketList/users/:userSerialId/groups/:groupSerialId/baskets/:basketId', canActivate: [AppGuard], component: BasketListComponent },
    { path: 'login', component: LoginComponent },
    { path: 'registeredMail/acknowledgement', canActivate: [AppGuard], component: AcknowledgementReceptionComponent },
    {
        path: '',
        redirectTo: 'home',
        pathMatch: 'full'
    },
];
@NgModule({
    imports: [
        RouterModule.forRoot(routes, { preloadingStrategy: PreloadAllModules, useHash: true })
    ],
    exports: [
        RouterModule
    ]
})
export class AppRoutingModule { }
