import { CommonModule } from '@angular/common';

import { NgModule } from '@angular/core';

/*CORE IMPORTS*/
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { DragDropModule } from '@angular/cdk/drag-drop';


/*PLUGINS IMPORTS*/
import { AppServiceModule } from './app-service.module';
import { NotificationModule } from '../service/notification/notification.module';

import { MaarchTreeComponent } from '../plugins/tree/maarch-tree.component';
import { AutocompleteListComponent } from '../plugins/autocomplete-list/autocomplete-list.component';

/*FRONT IMPORTS*/
import { AppMaterialModule } from './app-material.module';

import { SmdFabSpeedDialComponent, SmdFabSpeedDialTrigger, SmdFabSpeedDialActions, } from '../plugins/fab-speed-dial';

/*MENU COMPONENT*/
import { HeaderRightComponent } from './header/header-right.component';
import { HeaderLeftComponent } from './header/header-left.component';
import { HeaderPanelComponent } from './header/header-panel.component';
import { MenuNavComponent } from './menu/menu-nav.component';
import { MenuShortcutComponent, IndexingGroupModalComponent } from './menu/menu-shortcut.component';

/*SEARCH*/
import { SearchHomeComponent } from './search/search-home.component';

/*SEARCH*/
import { BasketHomeComponent } from './basket/basket-home.component';

import { FieldListComponent } from './indexation/field-list/field-list.component';

/*MODAL*/
import { AlertComponent } from '../plugins/modal/alert.component';
import { ConfirmComponent } from '../plugins/modal/confirm.component';

/*PLUGIN COMPONENT*/
import { NotesListComponent } from './notes/notes.component';
import { NoteEditorComponent } from './notes/note-editor.component';

import { PluginAutocomplete } from '../plugins/autocomplete/autocomplete.component';
import { PluginSelectSearchComponent } from '../plugins/select-search/select-search.component';

import { DragDropDirective } from '../app/viewer/upload-file-dnd.directive';
import { AddressBanAutocompleteComponent } from './contact/ban-autocomplete/address-ban-autocomplete.component';

import { ContactAutocompleteComponent } from './contact/autocomplete/contact-autocomplete.component';
import { ContactsFormComponent } from './administration/contact/page/form/contacts-form.component';

import { HistoryComponent } from './history/history.component';

import { DiffusionsListComponent } from './diffusions/diffusions-list.component';
import { VisaWorkflowComponent } from './visa/visa-workflow.component';
import { AvisWorkflowComponent } from './avis/avis-workflow.component';

import { ContactResourceComponent } from './contact/contact-resource/contact-resource.component';
import { ContactDetailComponent } from './contact/contact-detail/contact-detail.component';
import { AttachmentsListComponent } from './attachments/attachments-list.component';

import { FolderMenuComponent } from './folder/folder-menu/folder-menu.component';
import { FolderActionListComponent } from './folder/folder-action-list/folder-action-list.component';

import { LinkedResourceListComponent } from './linkedResource/linked-resource-list.component';

@NgModule({
    imports: [
        CommonModule,
        RouterModule,
        FormsModule,
        ReactiveFormsModule,
        AppMaterialModule,
        DragDropModule,
        AppServiceModule,
        NotificationModule
    ],
    declarations: [
        MenuNavComponent,
        MenuShortcutComponent,
        HeaderRightComponent,
        HeaderLeftComponent,
        HeaderPanelComponent,
        SearchHomeComponent,
        BasketHomeComponent,
        IndexingGroupModalComponent,
        SmdFabSpeedDialComponent,
        SmdFabSpeedDialTrigger,
        SmdFabSpeedDialActions,
        AlertComponent,
        ConfirmComponent,
        PluginAutocomplete,
        FieldListComponent,
        PluginSelectSearchComponent,
        DiffusionsListComponent,
        DragDropDirective,
        ContactAutocompleteComponent,
        ContactsFormComponent,
        HistoryComponent,
        AddressBanAutocompleteComponent,
        VisaWorkflowComponent,
        AvisWorkflowComponent,
        MaarchTreeComponent,
        ContactResourceComponent,
        ContactDetailComponent,
        AutocompleteListComponent,
        AttachmentsListComponent,
        FolderMenuComponent,
        FolderActionListComponent,
        LinkedResourceListComponent,
        NotesListComponent,
        NoteEditorComponent,

    ],
    exports: [
        CommonModule,
        MenuNavComponent,
        MenuShortcutComponent,
        HeaderRightComponent,
        HeaderLeftComponent,
        HeaderPanelComponent,
        SearchHomeComponent,
        BasketHomeComponent,
        FormsModule,
        ReactiveFormsModule,
        RouterModule,
        AppMaterialModule,
        AppServiceModule,
        NotificationModule,
        SmdFabSpeedDialComponent,
        SmdFabSpeedDialTrigger,
        SmdFabSpeedDialActions,
        DragDropModule,
        PluginAutocomplete,
        FieldListComponent,
        PluginSelectSearchComponent,
        DiffusionsListComponent,
        DragDropDirective,
        ContactAutocompleteComponent,
        ContactsFormComponent,
        HistoryComponent,
        AddressBanAutocompleteComponent,
        VisaWorkflowComponent,
        AvisWorkflowComponent,
        MaarchTreeComponent,
        ContactResourceComponent,
        ContactDetailComponent,
        AutocompleteListComponent,
        AttachmentsListComponent,
        FolderMenuComponent,
        FolderActionListComponent,
        LinkedResourceListComponent,
        NotesListComponent,
        NoteEditorComponent,
    ],
    providers: [],
    entryComponents: [
        IndexingGroupModalComponent,
        AlertComponent,
        ConfirmComponent
    ],
})
export class SharedModule { }
