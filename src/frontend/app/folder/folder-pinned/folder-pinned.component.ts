import { Component, OnInit, Input } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { TranslateService } from '@ngx-translate/core';

import { Subscription } from 'rxjs';
import { FoldersService } from '../folders.service';

@Component({
    selector: 'folder-pinned',
    templateUrl: 'folder-pinned.component.html',
    styleUrls: ['folder-pinned.component.scss'],
})
export class FolderPinnedComponent implements OnInit {

    lang: any = LANG;

    subscription: Subscription;

    @Input('noInit') noInit: boolean = false;

    constructor(
        private translate: TranslateService,
        public http: HttpClient,
        public foldersService: FoldersService
    ) {
        // Event after process action
        this.subscription = this.foldersService.catchEvent().subscribe((result: any) => {
            //console.log(result);
        });
    }

    ngOnInit(): void {
        this.foldersService.initFolder();
        if (!this.noInit) {
            this.foldersService.getPinnedFolders();
        }
    }

    gotToFolder(folder: any) {
        this.foldersService.goToFolder(folder);
    }

    dragEnter(folder: any) {
        folder.drag = true;
    }

    drop(ev: any, node: any) {
        this.foldersService.classifyDocument(ev, node);
    }

    ngOnDestroy() {
        // unsubscribe to ensure no memory leaks
        this.subscription.unsubscribe();
    }

}
