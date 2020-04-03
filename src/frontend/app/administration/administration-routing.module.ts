import { NgModule }     from '@angular/core';
import { RouterModule } from '@angular/router';

import { AdministrationComponent }                      from './home/administration.component';
import { UsersAdministrationComponent }                 from './user/users-administration.component';
import { UserAdministrationComponent }                  from './user/user-administration.component';
import { GroupsAdministrationComponent }                from './group/groups-administration.component';
import { GroupAdministrationComponent }                 from './group/group-administration.component';
import { BasketsAdministrationComponent }               from './basket/baskets-administration.component';
import { BasketAdministrationComponent }                from './basket/basket-administration.component';
import { DoctypesAdministrationComponent }              from './doctype/doctypes-administration.component';
import { DiffusionModelsAdministrationComponent }       from './diffusionModel/diffusionModels-administration.component';
import { DiffusionModelAdministrationComponent }        from './diffusionModel/diffusionModel-administration.component';
import { EntitiesAdministrationComponent }              from './entity/entities-administration.component';
import { StatusesAdministrationComponent }              from './status/statuses-administration.component';
import { StatusAdministrationComponent }                from './status/status-administration.component';
import { ActionsAdministrationComponent }               from './action/actions-administration.component';
import { ActionAdministrationComponent }                from './action/action-administration.component';
import { ParameterAdministrationComponent }             from './parameter/parameter-administration.component';
import { ParametersAdministrationComponent }            from './parameter/parameters-administration.component';
import { PrioritiesAdministrationComponent }            from './priority/priorities-administration.component';
import { PriorityAdministrationComponent }              from './priority/priority-administration.component';
import { NotificationsAdministrationComponent }         from './notification/notifications-administration.component';
import { NotificationAdministrationComponent }          from './notification/notification-administration.component';
import { HistoryAdministrationComponent }               from './history/history-administration.component';
import { HistoryBatchAdministrationComponent }          from './history/batch/history-batch-administration.component';
import { UpdateStatusAdministrationComponent }          from './updateStatus/update-status-administration.component';
import { ContactsGroupsAdministrationComponent }        from './contact/group/contacts-groups-administration.component';
import { ContactsGroupAdministrationComponent }         from './contact/group/contacts-group-administration.component';
import { ContactsParametersAdministrationComponent }       from './contact/parameter/contacts-parameters-administration.component';
import { VersionsUpdateAdministrationComponent }        from './versionUpdate/versions-update-administration.component';
import { DocserversAdministrationComponent }            from './docserver/docservers-administration.component';
import { DocserverAdministrationComponent }             from './docserver/docserver-administration.component';
import { TemplatesAdministrationComponent }             from './template/templates-administration.component';
import { TemplateAdministrationComponent }              from './template/template-administration.component';
import { SecuritiesAdministrationComponent }            from './security/securities-administration.component';
import { SendmailAdministrationComponent }              from './sendmail/sendmail-administration.component';
import { ShippingsAdministrationComponent }             from './shipping/shippings-administration.component';
import { ShippingAdministrationComponent }              from './shipping/shipping-administration.component';
import { CustomFieldsAdministrationComponent }          from './customField/custom-fields-administration.component';
import { AppGuard }                                     from '../../service/app.guard';
import { IndexingModelAdministrationComponent }        from './indexingModel/indexing-model-administration.component';
import { IndexingModelsAdministrationComponent }        from './indexingModel/indexing-models-administration.component';
import { ContactsListAdministrationComponent }        from './contact/list/contacts-list-administration.component';
import { ContactsCustomFieldsAdministrationComponent } from './contact/customField/contacts-custom-fields-administration.component';
import { ContactsPageAdministrationComponent } from './contact/page/contacts-page-administration.component';
import { TagsAdministrationComponent } from './tag/tags-administration.component';
import { TagAdministrationComponent } from './tag/tag-administration.component';

@NgModule({
    imports: [
        RouterModule.forChild([
            { path: 'administration', canActivate: [AppGuard], component: AdministrationComponent },
            { path: 'administration/users', canActivate: [AppGuard], component: UsersAdministrationComponent },
            { path: 'administration/users/new', canActivate: [AppGuard], component: UserAdministrationComponent },
            { path: 'administration/users/:id', canActivate: [AppGuard], component: UserAdministrationComponent },
            { path: 'administration/groups', canActivate: [AppGuard], component: GroupsAdministrationComponent },
            { path: 'administration/groups/new', canActivate: [AppGuard], component: GroupAdministrationComponent },
            { path: 'administration/groups/:id', canActivate: [AppGuard], component: GroupAdministrationComponent },
            { path: 'administration/baskets', canActivate: [AppGuard], component: BasketsAdministrationComponent },
            { path: 'administration/baskets/new', canActivate: [AppGuard], component: BasketAdministrationComponent },
            { path: 'administration/baskets/:id', canActivate: [AppGuard], component: BasketAdministrationComponent },
            { path: 'administration/doctypes', canActivate: [AppGuard], component: DoctypesAdministrationComponent },
            { path: 'administration/diffusionModels', canActivate: [AppGuard], component: DiffusionModelsAdministrationComponent },
            { path: 'administration/diffusionModels/new', canActivate: [AppGuard], component: DiffusionModelAdministrationComponent },
            { path: 'administration/diffusionModels/:id', canActivate: [AppGuard], component: DiffusionModelAdministrationComponent },
            { path: 'administration/entities', canActivate: [AppGuard], component: EntitiesAdministrationComponent },
            { path: 'administration/statuses', canActivate: [AppGuard], component: StatusesAdministrationComponent },
            { path: 'administration/statuses/new', canActivate: [AppGuard], component: StatusAdministrationComponent },
            { path: 'administration/statuses/:identifier', canActivate: [AppGuard], component: StatusAdministrationComponent },
            { path: 'administration/parameters', canActivate: [AppGuard], component: ParametersAdministrationComponent },
            { path: 'administration/parameters/new', canActivate: [AppGuard], component: ParameterAdministrationComponent },
            { path: 'administration/parameters/:id', canActivate: [AppGuard], component: ParameterAdministrationComponent },
            { path: 'administration/priorities', canActivate: [AppGuard], component : PrioritiesAdministrationComponent },
            { path: 'administration/priorities/new', canActivate: [AppGuard], component : PriorityAdministrationComponent },
            { path: 'administration/priorities/:id', canActivate: [AppGuard], component : PriorityAdministrationComponent },
            { path: 'administration/actions', canActivate: [AppGuard], component: ActionsAdministrationComponent },
            { path: 'administration/actions/new', canActivate: [AppGuard], component: ActionAdministrationComponent },
            { path: 'administration/actions/:id', canActivate: [AppGuard], component: ActionAdministrationComponent },
            { path: 'administration/notifications', canActivate: [AppGuard], component: NotificationsAdministrationComponent },
            { path: 'administration/notifications/new', canActivate: [AppGuard], component: NotificationAdministrationComponent },
            { path: 'administration/notifications/:identifier', canActivate: [AppGuard], component: NotificationAdministrationComponent },
            { path: 'administration/history', canActivate: [AppGuard], component: HistoryAdministrationComponent },
            { path: 'administration/history-batch', canActivate: [AppGuard], component: HistoryBatchAdministrationComponent },
            { path: 'administration/update-status', canActivate: [AppGuard], component: UpdateStatusAdministrationComponent },
            { path: 'administration/contacts', canActivate: [AppGuard], component: ContactsListAdministrationComponent },
            { path: 'administration/contacts/list', redirectTo: 'administration/contacts', pathMatch: 'full' },
            { path: 'administration/contacts/list/new', canActivate: [AppGuard], component: ContactsPageAdministrationComponent },
            { path: 'administration/contacts/list/:id', canActivate: [AppGuard], component: ContactsPageAdministrationComponent },
            { path: 'administration/contacts/contactsCustomFields', canActivate: [AppGuard], component: ContactsCustomFieldsAdministrationComponent },
            { path: 'administration/contacts/contacts-groups', canActivate: [AppGuard], component: ContactsGroupsAdministrationComponent },
            { path: 'administration/contacts/contacts-groups/new', canActivate: [AppGuard], component: ContactsGroupAdministrationComponent },
            { path: 'administration/contacts/contacts-groups/:id', canActivate: [AppGuard], component: ContactsGroupAdministrationComponent },
            { path: 'administration/contacts/contacts-parameters', canActivate: [AppGuard], component: ContactsParametersAdministrationComponent },
            { path: 'administration/versions-update', canActivate: [AppGuard], component: VersionsUpdateAdministrationComponent },
            { path: 'administration/docservers', canActivate: [AppGuard], component: DocserversAdministrationComponent },
            { path: 'administration/docservers/new', canActivate: [AppGuard], component: DocserverAdministrationComponent },
            { path: 'administration/templates', canActivate: [AppGuard], component: TemplatesAdministrationComponent },
            { path: 'administration/templates/new', canActivate: [AppGuard], component: TemplateAdministrationComponent },
            { path: 'administration/templates/:id', canActivate: [AppGuard], component: TemplateAdministrationComponent },
            { path: 'administration/securities', canActivate: [AppGuard], component: SecuritiesAdministrationComponent },
            { path: 'administration/sendmail', canActivate: [AppGuard], component: SendmailAdministrationComponent },
            { path: 'administration/shippings', canActivate: [AppGuard], component: ShippingsAdministrationComponent },
            { path: 'administration/shippings/new', canActivate: [AppGuard], component: ShippingAdministrationComponent },
            { path: 'administration/shippings/:id', canActivate: [AppGuard], component: ShippingAdministrationComponent },
            { path: 'administration/customFields', canActivate: [AppGuard], component: CustomFieldsAdministrationComponent },
            { path: 'administration/indexingModels', canActivate: [AppGuard], component: IndexingModelsAdministrationComponent },
            { path: 'administration/indexingModels/new', canActivate: [AppGuard], component: IndexingModelAdministrationComponent },
            { path: 'administration/indexingModels/:id', canActivate: [AppGuard], component: IndexingModelAdministrationComponent },
            { path: 'administration/tags', canActivate: [AppGuard], component: TagsAdministrationComponent },
            { path: 'administration/tags/new', canActivate: [AppGuard], component: TagAdministrationComponent },
            { path: 'administration/tags/:id', canActivate: [AppGuard], component: TagAdministrationComponent },
        ]),
    ],
    exports: [
        RouterModule
    ]
})
export class AdministrationRoutingModule {}
