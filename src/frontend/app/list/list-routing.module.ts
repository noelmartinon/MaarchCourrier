import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { BasketListComponent } from './basket-list.component';
import { AppGuard } from '../../service/app.guard';

const routes: Routes = [
  {
    path: '',
    canActivate: [AppGuard],
    component: BasketListComponent
  }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class ListRoutingModule {}
