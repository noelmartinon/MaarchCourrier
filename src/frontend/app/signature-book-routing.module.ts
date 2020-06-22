import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { SignatureBookComponent } from './signature-book.component';
import { AppGuard } from '../service/app.guard';

const routes: Routes = [
  {
    path: '',
    canActivate: [AppGuard],
    component: SignatureBookComponent
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class SignatureBookRoutingModule {}
