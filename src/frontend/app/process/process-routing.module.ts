import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { ProcessComponent } from './process.component';
import { IndexationComponent } from '../indexation/indexation.component';
import { AppGuard, AfterProcessGuard } from '../../service/app.guard';

const routes: Routes = [
  {
    path: 'process/users/:userSerialId/groups/:groupSerialId/baskets/:basketId/resId/:resId',
    canActivate: [AppGuard],
    canDeactivate: [AfterProcessGuard],
    component: ProcessComponent
  },
  {
    path: 'resources/:detailResId',
    canActivate: [AppGuard],
    canDeactivate: [AfterProcessGuard],
    component: ProcessComponent
  },
  {
    path: 'indexing/:groupId',
    canActivate: [AppGuard],
    component: IndexationComponent
  },
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class ProcessRoutingModule {}
