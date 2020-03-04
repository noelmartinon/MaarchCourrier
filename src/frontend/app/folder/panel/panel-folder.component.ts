import { Component, OnInit, Input, ViewChild, Output, EventEmitter } from '@angular/core';
import { LANG } from '../../translate.component';
import { FolderTreeComponent } from '../folder-tree.component';
import { FoldersService } from '../folders.service';

declare function $j(selector: any): any;

@Component({
    selector: 'panel-folder',
    templateUrl: "panel-folder.component.html",
    styleUrls: ['panel-folder.component.scss'],
})
export class PanelFolderComponent implements OnInit {

    lang: any = LANG;

    @Input('selectedId') id: number;
    @Input('showTree') showTree: boolean = false;
    @ViewChild('folderTree', { static: false }) folderTree: FolderTreeComponent;
    
    @Output('refreshEvent') refreshEvent = new EventEmitter<string>();
    
    constructor(public foldersService: FoldersService) { }

    ngOnInit(): void { }

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