import { NgModule } from '@angular/core';
import { SharedModule } from './app-common.module';
import { FiltersToolComponent } from './list/filters/filters-tool.component';
import { PanelListComponent } from './list/panel/panel-list.component';
import { SummarySheetComponent } from './list/summarySheet/summary-sheet.component';
import { ExportComponent } from './list/export/export.component';
import { ToolsListComponent } from './list/tools/tools-list.component';
import { PrintedFolderModalComponent } from './printedFolder/printed-folder-modal.component';
import { ActionsListComponent } from './actions/actions-list.component';
import { FollowedActionListComponent } from './home/followed-action-list/followed-action-list.component';
import { NgPipesModule } from 'ngx-pipes';
import { NoteModule } from './notes/note.module';

@NgModule({
    imports: [
        SharedModule,
        NgPipesModule,
        NoteModule
    ],
    declarations: [
        FiltersToolComponent,
        PanelListComponent,
        SummarySheetComponent,
        ExportComponent,
        ToolsListComponent,
        PrintedFolderModalComponent,
        ActionsListComponent,
        FollowedActionListComponent,

    ],
    exports: [
        FiltersToolComponent,
        PanelListComponent,
        SummarySheetComponent,
        ExportComponent,
        ToolsListComponent,
        PrintedFolderModalComponent,
        ActionsListComponent,
        FollowedActionListComponent,
    ],
    entryComponents: [
        SummarySheetComponent,
        ExportComponent,
        PrintedFolderModalComponent,
    ],
})
export class AppListModule { }
