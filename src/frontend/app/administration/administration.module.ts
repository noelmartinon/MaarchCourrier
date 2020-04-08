import { NgModule } from '@angular/core';

import { SharedModule } from '../app-common.module';

import { AdministrationRoutingModule } from './administration-routing.module';
import { NgxChartsModule } from '@swimlane/ngx-charts';

import { AdministrationComponent } from './home/administration.component';
import { UsersAdministrationComponent, UsersAdministrationRedirectModalComponent } from './user/users-administration.component';
import { AccountLinkComponent } from './user/account-link/account-link.component';
import { GroupsAdministrationComponent, GroupsAdministrationRedirectModalComponent } from './group/groups-administration.component';
import { UserAdministrationComponent, UserAdministrationRedirectModalComponent } from './user/user-administration.component';
import { GroupAdministrationComponent } from './group/group-administration.component';
import { IndexingAdministrationComponent } from './group/indexing/indexing-administration.component';
import { BasketsAdministrationComponent } from './basket/baskets-administration.component';
import { BasketAdministrationComponent, BasketAdministrationSettingsModalComponent, BasketAdministrationGroupListModalComponent } from './basket/basket-administration.component';
import { EntitiesAdministrationComponent, EntitiesAdministrationRedirectModalComponent } from './entity/entities-administration.component';
import { DiffusionModelsAdministrationComponent } from './diffusionModel/diffusionModels-administration.component';
import { DiffusionModelAdministrationComponent } from './diffusionModel/diffusionModel-administration.component';
import { DoctypesAdministrationComponent, DoctypesAdministrationRedirectModalComponent } from './doctype/doctypes-administration.component';
import { StatusesAdministrationComponent } from './status/statuses-administration.component';
import { StatusAdministrationComponent } from './status/status-administration.component';
import { ActionsAdministrationComponent } from './action/actions-administration.component';
import { ActionAdministrationComponent } from './action/action-administration.component';
import { ParametersAdministrationComponent } from './parameter/parameters-administration.component';
import { ParameterAdministrationComponent } from './parameter/parameter-administration.component';
import { PrioritiesAdministrationComponent } from './priority/priorities-administration.component';
import { PriorityAdministrationComponent } from './priority/priority-administration.component';
import { HistoryAdministrationComponent } from './history/history-administration.component';
import { HistoryBatchAdministrationComponent } from './history/batch/history-batch-administration.component';
import { UpdateStatusAdministrationComponent } from './updateStatus/update-status-administration.component';
import { NotificationsAdministrationComponent } from './notification/notifications-administration.component';
import { NotificationAdministrationComponent } from './notification/notification-administration.component';
import { ContactsGroupsAdministrationComponent } from './contact/group/contacts-groups-administration.component';
import { ContactsGroupAdministrationComponent } from './contact/group/contacts-group-administration.component';
import { ContactsParametersAdministrationComponent } from './contact/parameter/contacts-parameters-administration.component';
import { VersionsUpdateAdministrationComponent } from './versionUpdate/versions-update-administration.component';
import { DocserversAdministrationComponent } from './docserver/docservers-administration.component';
import { DocserverAdministrationComponent } from './docserver/docserver-administration.component';
import { TemplatesAdministrationComponent } from './template/templates-administration.component';
import { TemplateAdministrationComponent, TemplateAdministrationCheckEntitiesModalComponent } from './template/template-administration.component';
import { SecuritiesAdministrationComponent } from './security/securities-administration.component';
import { SendmailAdministrationComponent } from './sendmail/sendmail-administration.component';
import { ListAdministrationComponent } from './basket/list/list-administration.component';
import { ShippingsAdministrationComponent } from './shipping/shippings-administration.component';
import { ShippingAdministrationComponent } from './shipping/shipping-administration.component';
import { CustomFieldsAdministrationComponent } from './customField/custom-fields-administration.component';
import { IndexingModelAdministrationComponent } from './indexingModel/indexing-model-administration.component';
import { IndexingModelsAdministrationComponent } from './indexingModel/indexing-models-administration.component';
import { ContactsListAdministrationComponent, ContactsListAdministrationRedirectModalComponent } from './contact/list/contacts-list-administration.component';
import { ContactsCustomFieldsAdministrationComponent } from './contact/customField/contacts-custom-fields-administration.component';
import { ContactsPageAdministrationComponent } from './contact/page/contacts-page-administration.component';
import { TagsAdministrationComponent } from './tag/tags-administration.component';
import { TagAdministrationComponent } from './tag/tag-administration.component';
import { TemplateFileEditorModalComponent } from './template/templateFileEditorModal/template-file-editor-modal.component';



@NgModule({
    imports: [
        SharedModule,
        NgxChartsModule,
        AdministrationRoutingModule
    ],
    declarations: [
        AdministrationComponent,
        UsersAdministrationComponent,
        UserAdministrationComponent,
        GroupsAdministrationComponent,
        GroupAdministrationComponent,
        IndexingAdministrationComponent,
        BasketsAdministrationComponent,
        BasketAdministrationComponent,
        DoctypesAdministrationComponent,
        EntitiesAdministrationComponent,
        StatusesAdministrationComponent,
        StatusAdministrationComponent,
        ActionsAdministrationComponent,
        ActionAdministrationComponent,
        ParametersAdministrationComponent,
        ParameterAdministrationComponent,
        PrioritiesAdministrationComponent,
        PriorityAdministrationComponent,
        HistoryAdministrationComponent,
        HistoryBatchAdministrationComponent,
        UpdateStatusAdministrationComponent,
        ContactsGroupsAdministrationComponent,
        ContactsGroupAdministrationComponent,
        ContactsParametersAdministrationComponent,
        NotificationsAdministrationComponent,
        NotificationAdministrationComponent,
        UsersAdministrationRedirectModalComponent,
        UserAdministrationRedirectModalComponent,
        EntitiesAdministrationRedirectModalComponent,
        GroupsAdministrationRedirectModalComponent,
        BasketAdministrationSettingsModalComponent,
        BasketAdministrationGroupListModalComponent,
        DoctypesAdministrationRedirectModalComponent,
        DiffusionModelsAdministrationComponent,
        DiffusionModelAdministrationComponent,
        VersionsUpdateAdministrationComponent,
        DocserversAdministrationComponent,
        DocserverAdministrationComponent,
        TemplatesAdministrationComponent,
        TemplateAdministrationComponent,
        SecuritiesAdministrationComponent,
        SendmailAdministrationComponent,
        ListAdministrationComponent,
        TemplateAdministrationCheckEntitiesModalComponent,
        ShippingsAdministrationComponent,
        ShippingAdministrationComponent,
        AccountLinkComponent,
        CustomFieldsAdministrationComponent,
        IndexingModelAdministrationComponent,
        IndexingModelsAdministrationComponent,
        ContactsListAdministrationComponent,
        ContactsListAdministrationRedirectModalComponent,
        ContactsCustomFieldsAdministrationComponent,
        ContactsPageAdministrationComponent,
        TagsAdministrationComponent,
        TagAdministrationComponent,
        TemplateFileEditorModalComponent
    ],
    entryComponents: [
        UsersAdministrationRedirectModalComponent,
        UserAdministrationRedirectModalComponent,
        EntitiesAdministrationRedirectModalComponent,
        GroupsAdministrationRedirectModalComponent,
        BasketAdministrationSettingsModalComponent,
        BasketAdministrationGroupListModalComponent,
        DoctypesAdministrationRedirectModalComponent,
        ContactsListAdministrationRedirectModalComponent,
        TemplateAdministrationCheckEntitiesModalComponent,
        AccountLinkComponent,
        TemplateFileEditorModalComponent
    ],
})
export class AdministrationModule { }
