import { Component, OnInit, Input, ViewChild, Output, EventEmitter, ChangeDetectorRef } from '@angular/core';
import { LANG } from '../../translate.component';
import { FolderTreeComponent } from '../folder-tree.component';
import { FoldersService } from '../folders.service';
import { HeaderService } from '../../../service/header.service';
import { ActionsService } from '../../actions/actions.service';
import { Subscription } from 'rxjs';

declare function $j(selector: any): any;

@Component({
    selector: 'panel-folder',
    templateUrl: "panel-folder.component.html",
    styleUrls: ['panel-folder.component.scss'],
})
export class PanelFolderComponent implements OnInit {

    lang: any = LANG;

    @Input('selectedId') id: number;
    
    @ViewChild('folderTree', { static: false }) folderTree: FolderTreeComponent;
    
    @Output('refreshEvent') refreshEvent = new EventEmitter<string>();

    subscription: Subscription;
    
    constructor(
        public foldersService: FoldersService,
        public actionService: ActionsService,
        private changeDetectorRef: ChangeDetectorRef
        ) {
        this.subscription = this.actionService.catchAction().subscribe(message => {

            this.refreshFoldersTree();
        });
    }

    ngOnInit(): void {
        this.foldersService.getPinnedFolders();
    }

    ngOnDestroy() {
        // unsubscribe to ensure no memory leaks
        this.subscription.unsubscribe();
    }

    initTree() {
        this.folderTree.openTree(this.id);
    }

    refreshDocList() {
        this.refreshEvent.emit();
    }

    refreshFoldersTree() {
        if (this.folderTree !== undefined) {
            this.folderTree.getFolders();
        }
    }
}
