import { NgModule }                         from '@angular/core';
import { RouterModule }                     from '@angular/router';

import { ActivateUserComponent }            from './activate-user.component';
import { PasswordModificationComponent }    from './password-modification.component';
import { ProfileComponent }                 from './profile.component';
import { AboutUsComponent }                 from './about-us.component';
import { HomeComponent }                    from './home/home.component';
import { BasketListComponent }              from './list/basket-list.component';
import { SignatureBookComponent }           from './signature-book.component';
import { SaveNumericPackageComponent }      from './save-numeric-package.component';
import { AppGuard }                         from '../service/app.guard';

@NgModule({
    imports: [
        RouterModule.forRoot([
            { path: 'activate-user', component: ActivateUserComponent},
            { path: 'password-modification', component: PasswordModificationComponent },
            { path: 'profile', canActivate: [AppGuard], component: ProfileComponent },
            { path: 'about-us', canActivate: [AppGuard], component: AboutUsComponent },
            { path: 'home', canActivate: [AppGuard],  component: HomeComponent },
            { path: 'basketList/users/:userSerialId/groups/:groupSerialId/baskets/:basketId', canActivate: [AppGuard], component: BasketListComponent },
            { path: 'saveNumericPackage', canActivate: [AppGuard], component: SaveNumericPackageComponent },
            { path: 'signatureBook/users/:userId/groups/:groupId/baskets/:basketId/resources/:resId', canActivate: [AppGuard],component: SignatureBookComponent },
            { path: '**',  redirectTo: 'home', pathMatch: 'full' },
        ], { useHash: true }),
    ],
    exports: [
        RouterModule
    ]
})
export class AppRoutingModule {}
