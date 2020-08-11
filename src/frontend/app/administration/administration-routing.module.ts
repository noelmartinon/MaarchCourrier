import { NgModule } from '@angular/core';
import { RouterModule } from '@angular/router';

import { AdministrationComponent } from './home/administration.component';
import { UsersAdministrationComponent } from './user/users-administration.component';
import { UserAdministrationComponent } from './user/user-administration.component';
import { GroupsAdministrationComponent } from './group/groups-administration.component';
import { GroupAdministrationComponent } from './group/group-administration.component';
import { BasketsAdministrationComponent } from './basket/baskets-administration.component';
import { BasketAdministrationComponent } from './basket/basket-administration.component';
import { DoctypesAdministrationComponent } from './doctype/doctypes-administration.component';
import { DiffusionModelsAdministrationComponent } from './diffusionModel/diffusionModels-administration.component';
import { DiffusionModelAdministrationComponent } from './diffusionModel/diffusionModel-administration.component';
import { EntitiesAdministrationComponent } from './entity/entities-administration.component';
import { StatusesAdministrationComponent } from './status/statuses-administration.component';
import { StatusAdministrationComponent } from './status/status-administration.component';
import { ActionsAdministrationComponent } from './action/actions-administration.component';
import { ActionAdministrationComponent } from './action/action-administration.component';
import { ParameterAdministrationComponent } from './parameter/parameter-administration.component';
import { ParametersAdministrationComponent } from './parameter/parameters-administration.component';
import { PrioritiesAdministrationComponent } from './priority/priorities-administration.component';
import { PriorityAdministrationComponent } from './priority/priority-administration.component';
import { NotificationsAdministrationComponent } from './notification/notifications-administration.component';
import { NotificationAdministrationComponent } from './notification/notification-administration.component';
import { HistoryAdministrationComponent } from './history/history-administration.component';
import { HistoryBatchAdministrationComponent } from './history/batch/history-batch-administration.component';
import { UpdateStatusAdministrationComponent } from './updateStatus/update-status-administration.component';
import { ContactsGroupsAdministrationComponent } from './contact/group/contacts-groups-administration.component';
import { ContactsGroupAdministrationComponent } from './contact/group/contacts-group-administration.component';
import { ContactsParametersAdministrationComponent } from './contact/parameter/contacts-parameters-administration.component';
import { VersionsUpdateAdministrationComponent } from './versionUpdate/versions-update-administration.component';
import { DocserversAdministrationComponent } from './docserver/docservers-administration.component';
import { DocserverAdministrationComponent } from './docserver/docserver-administration.component';
import { TemplatesAdministrationComponent } from './template/templates-administration.component';
import { TemplateAdministrationComponent } from './template/template-administration.component';
import { SecuritiesAdministrationComponent } from './security/securities-administration.component';
import { SendmailAdministrationComponent } from './sendmail/sendmail-administration.component';
import { ShippingsAdministrationComponent } from './shipping/shippings-administration.component';
import { ShippingAdministrationComponent } from './shipping/shipping-administration.component';
import { CustomFieldsAdministrationComponent } from './customField/custom-fields-administration.component';
import { AppGuard } from '../../service/app.guard';
import { IndexingModelAdministrationComponent } from './indexingModel/indexing-model-administration.component';
import { IndexingModelsAdministrationComponent } from './indexingModel/indexing-models-administration.component';
import { ContactsListAdministrationComponent } from './contact/list/contacts-list-administration.component';
import { ContactsCustomFieldsAdministrationComponent } from './contact/customField/contacts-custom-fields-administration.component';
import { ContactsPageAdministrationComponent } from './contact/page/contacts-page-administration.component';
import { TagsAdministrationComponent } from './tag/tags-administration.component';
import { TagAdministrationComponent } from './tag/tag-administration.component';
import { AlfrescoAdministrationComponent } from './alfresco/alfresco-administration.component';
import { AlfrescoListAdministrationComponent } from './alfresco/alfresco-list-administration.component';
import { ContactDuplicateComponent } from './contact/contact-duplicate/contact-duplicate.component';
import { IssuingSiteListComponent } from './registered-mail/issuing-site/issuing-site-list.component';
import { IssuingSiteComponent } from './registered-mail/issuing-site/issuing-site.component';

@NgModule({
    imports: [
        RouterModule.forChild([
            { path: '', canActivate: [AppGuard], component: AdministrationComponent },
            { path: 'users', canActivate: [AppGuard], component: UsersAdministrationComponent },
            { path: 'users/new', canActivate: [AppGuard], component: UserAdministrationComponent },
            { path: 'users/:id', canActivate: [AppGuard], component: UserAdministrationComponent },
            { path: 'groups', canActivate: [AppGuard], component: GroupsAdministrationComponent },
            { path: 'groups/new', canActivate: [AppGuard], component: GroupAdministrationComponent },
            { path: 'groups/:id', canActivate: [AppGuard], component: GroupAdministrationComponent },
            { path: 'baskets', canActivate: [AppGuard], component: BasketsAdministrationComponent },
            { path: 'baskets/new', canActivate: [AppGuard], component: BasketAdministrationComponent },
            { path: 'baskets/:id', canActivate: [AppGuard], component: BasketAdministrationComponent },
            { path: 'doctypes', canActivate: [AppGuard], component: DoctypesAdministrationComponent },
            { path: 'diffusionModels', canActivate: [AppGuard], component: DiffusionModelsAdministrationComponent },
            { path: 'diffusionModels/new', canActivate: [AppGuard], component: DiffusionModelAdministrationComponent },
            { path: 'diffusionModels/:id', canActivate: [AppGuard], component: DiffusionModelAdministrationComponent },
            { path: 'entities', canActivate: [AppGuard], component: EntitiesAdministrationComponent },
            { path: 'statuses', canActivate: [AppGuard], component: StatusesAdministrationComponent },
            { path: 'statuses/new', canActivate: [AppGuard], component: StatusAdministrationComponent },
            { path: 'statuses/:identifier', canActivate: [AppGuard], component: StatusAdministrationComponent },
            { path: 'parameters', canActivate: [AppGuard], component: ParametersAdministrationComponent },
            { path: 'parameters/new', canActivate: [AppGuard], component: ParameterAdministrationComponent },
            { path: 'parameters/:id', canActivate: [AppGuard], component: ParameterAdministrationComponent },
            { path: 'priorities', canActivate: [AppGuard], component: PrioritiesAdministrationComponent },
            { path: 'priorities/new', canActivate: [AppGuard], component: PriorityAdministrationComponent },
            { path: 'priorities/:id', canActivate: [AppGuard], component: PriorityAdministrationComponent },
            { path: 'actions', canActivate: [AppGuard], component: ActionsAdministrationComponent },
            { path: 'actions/new', canActivate: [AppGuard], component: ActionAdministrationComponent },
            { path: 'actions/:id', canActivate: [AppGuard], component: ActionAdministrationComponent },
            { path: 'notifications', canActivate: [AppGuard], component: NotificationsAdministrationComponent },
            { path: 'notifications/new', canActivate: [AppGuard], component: NotificationAdministrationComponent },
            { path: 'notifications/:identifier', canActivate: [AppGuard], component: NotificationAdministrationComponent },
            { path: 'history', canActivate: [AppGuard], component: HistoryAdministrationComponent },
            { path: 'history-batch', canActivate: [AppGuard], component: HistoryBatchAdministrationComponent },
            { path: 'update-status', canActivate: [AppGuard], component: UpdateStatusAdministrationComponent },
            { path: 'contacts', canActivate: [AppGuard], component: ContactsListAdministrationComponent },
            { path: 'contacts/duplicates', canActivate: [AppGuard], component: ContactDuplicateComponent },
            { path: 'contacts/list', redirectTo: 'contacts', pathMatch: 'full' },
            { path: 'contacts/list/new', canActivate: [AppGuard], component: ContactsPageAdministrationComponent },
            { path: 'contacts/list/:id', canActivate: [AppGuard], component: ContactsPageAdministrationComponent },
            { path: 'contacts/contactsCustomFields', canActivate: [AppGuard], component: ContactsCustomFieldsAdministrationComponent },
            { path: 'contacts/contacts-groups', canActivate: [AppGuard], component: ContactsGroupsAdministrationComponent },
            { path: 'contacts/contacts-groups/new', canActivate: [AppGuard], component: ContactsGroupAdministrationComponent },
            { path: 'contacts/contacts-groups/:id', canActivate: [AppGuard], component: ContactsGroupAdministrationComponent },
            { path: 'contacts/contacts-parameters', canActivate: [AppGuard], component: ContactsParametersAdministrationComponent },
            { path: 'versions-update', canActivate: [AppGuard], component: VersionsUpdateAdministrationComponent },
            { path: 'docservers', canActivate: [AppGuard], component: DocserversAdministrationComponent },
            { path: 'docservers/new', canActivate: [AppGuard], component: DocserverAdministrationComponent },
            { path: 'templates', canActivate: [AppGuard], component: TemplatesAdministrationComponent },
            { path: 'templates/new', canActivate: [AppGuard], component: TemplateAdministrationComponent },
            { path: 'templates/:id', canActivate: [AppGuard], component: TemplateAdministrationComponent },
            { path: 'securities', canActivate: [AppGuard], component: SecuritiesAdministrationComponent },
            { path: 'sendmail', canActivate: [AppGuard], component: SendmailAdministrationComponent },
            { path: 'shippings', canActivate: [AppGuard], component: ShippingsAdministrationComponent },
            { path: 'shippings/new', canActivate: [AppGuard], component: ShippingAdministrationComponent },
            { path: 'shippings/:id', canActivate: [AppGuard], component: ShippingAdministrationComponent },
            { path: 'customFields', canActivate: [AppGuard], component: CustomFieldsAdministrationComponent },
            { path: 'indexingModels', canActivate: [AppGuard], component: IndexingModelsAdministrationComponent },
            { path: 'indexingModels/new', canActivate: [AppGuard], component: IndexingModelAdministrationComponent },
            { path: 'indexingModels/:id', canActivate: [AppGuard], component: IndexingModelAdministrationComponent },
            { path: 'tags', canActivate: [AppGuard], component: TagsAdministrationComponent },
            { path: 'tags/new', canActivate: [AppGuard], component: TagAdministrationComponent },
            { path: 'tags/:id', canActivate: [AppGuard], component: TagAdministrationComponent },
            { path: 'alfresco', canActivate: [AppGuard], component: AlfrescoListAdministrationComponent },
            { path: 'alfresco/new', canActivate: [AppGuard], component: AlfrescoAdministrationComponent },
            { path: 'alfresco/:id', canActivate: [AppGuard], component: AlfrescoAdministrationComponent },
            { path: 'issuingSite', canActivate: [AppGuard], component: IssuingSiteListComponent },
            { path: 'issuingSite/new', canActivate: [AppGuard], component: IssuingSiteComponent },
        ]),
    ],
    exports: [
        RouterModule
    ]
})
export class AdministrationRoutingModule { }
