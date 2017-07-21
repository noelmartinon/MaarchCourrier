import { NgModule }         from '@angular/core';
import { BrowserModule }    from '@angular/platform-browser';
import { RouterModule }     from '@angular/router';
import { HttpModule }       from '@angular/http';
import { FormsModule }      from '@angular/forms';

import { AppComponent }     from './app.component';
import { ProfileComponent } from './profile.component';
import { SaveNumericPackageComponent } from './save-numeric-package.component';
import { SignatureBookComponent, SafeUrlPipe }  from './signature-book.component';

@NgModule({
  imports:      [
      BrowserModule,
      FormsModule,
      RouterModule.forRoot([
          { path: 'profile', component: ProfileComponent },
          { path: 'saveNumericPackage', component: SaveNumericPackageComponent },
          { path: ':basketId/signatureBook/:resId', component: SignatureBookComponent },
          { path: '**',   redirectTo: '', pathMatch: 'full' },
      ], { useHash: true }),
      HttpModule
  ],
  declarations: [ AppComponent, ProfileComponent, SaveNumericPackageComponent, SignatureBookComponent, SafeUrlPipe ],
  bootstrap:    [ AppComponent]
})
export class AppModule { }
