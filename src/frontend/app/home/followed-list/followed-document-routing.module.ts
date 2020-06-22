import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { FollowedDocumentListComponent } from './followed-document-list.component';
import { AppGuard } from '../../../service/app.guard';

const routes: Routes = [
  {
    path: '',
    canActivate: [AppGuard],
    component: FollowedDocumentListComponent
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class FollowedDcumentRoutingModule {}
