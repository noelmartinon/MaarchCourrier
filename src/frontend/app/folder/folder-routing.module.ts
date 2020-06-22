import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { FolderDocumentListComponent } from './document-list/folder-document-list.component';
import { AppGuard } from '../../service/app.guard';

const routes: Routes = [
  {
    path: '',
    canActivate: [AppGuard],
    component: FolderDocumentListComponent
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class FolderRoutingModule {}
