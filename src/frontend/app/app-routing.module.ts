import { NgModule } from '@angular/core';
import { RouterModule, Routes, PreloadAllModules } from '@angular/router';

import { ActivateUserComponent } from './activate-user.component';
import { PasswordModificationComponent } from './login/passwordModification/password-modification.component';
import { ProfileComponent } from './profile.component';
import { AboutUsComponent } from './about-us.component';
import { HomeComponent } from './home/home.component';
import { SaveNumericPackageComponent } from './save-numeric-package.component';
import { PrintSeparatorComponent } from './separator/print-separator/print-separator.component';
import { AppGuard } from '../service/app.guard';
import { ForgotPasswordComponent } from './login/forgotPassword/forgotPassword.component';
import { ResetPasswordComponent } from './login/resetPassword/reset-password.component';
import { LoginComponent } from './login/login.component';
import { DocumentViewerPageComponent } from './viewer/page/document-viewer-page.component';

const routes: Routes = [
    {
        path: 'install',
        loadChildren: () => import('./installer/installer.module').then(m => m.InstallerModule)
    },
    {
        path: 'administration',
        loadChildren: () => import('./administration/administration.module').then(m => m.AdministrationModule)
    },
    { path: 'login', component: LoginComponent },
    { path: 'activate-user', component: ActivateUserComponent },
    { path: 'password-modification', component: PasswordModificationComponent },
    { path: 'profile', canActivate: [AppGuard], component: ProfileComponent },
    { path: 'about-us', canActivate: [AppGuard], component: AboutUsComponent },
    { path: 'home', canActivate: [AppGuard], component: HomeComponent },
    {
        path: 'basketList/users/:userSerialId/groups/:groupSerialId/baskets/:basketId',
        loadChildren: () => import('./list/list.module').then(m => m.ListModule)
    },
    {
        path: '',
        loadChildren: () => import('./process/process.module').then(m => m.ProcessModule)
    },
    {
        path: 'folders/:folderId',
        loadChildren: () => import('./folder/folder.module').then(m => m.FolderModule)
    },
    { path: 'saveNumericPackage', canActivate: [AppGuard], component: SaveNumericPackageComponent },
    { path: 'separators/print', canActivate: [AppGuard], component: PrintSeparatorComponent },
    {
        path: 'signatureBook/users/:userId/groups/:groupId/baskets/:basketId/resources/:resId',
        loadChildren: () => import('./signature-book.module').then(m => m.SignatureBookModule)
    },
    { path: 'forgot-password', component: ForgotPasswordComponent },
    { path: 'reset-password', component: ResetPasswordComponent },
    {
        path: 'followed',
        loadChildren: () => import('./home/followed-list/followed-document.module').then(m => m.FollowedDocumentModule)
    },
    { path: 'resources/:resId/content', canActivate: [AppGuard], component: DocumentViewerPageComponent },
    { path: '**', redirectTo: 'home', pathMatch: 'full' }
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
