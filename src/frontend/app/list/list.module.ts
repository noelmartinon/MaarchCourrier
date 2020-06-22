import { NgModule } from '@angular/core';

import { SharedModule } from '../app-common.module';
import { AppListModule } from '../app-list.module';

import { BasketListComponent } from './basket-list.component';

import { ListRoutingModule } from './list-routing.module';

@NgModule({
    imports: [
        SharedModule,
        AppListModule,
        ListRoutingModule,
    ],
    declarations: [
        BasketListComponent,
    ],
    entryComponents: [
    ]
})
export class ListModule { }
