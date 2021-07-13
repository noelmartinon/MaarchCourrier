import { Component, Input, OnInit, EventEmitter, Output } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../translate.component';
import { NotificationService } from '../notification.service';

@Component({
    selector: 'app-notes-list',
    templateUrl: 'notes-list.component.html',
    styleUrls: ['notes-list.component.scss'],
    providers: [NotificationService]
})
export class NotesListComponent implements OnInit {

    lang: any = LANG;
    notes: any[] = [];
    loading: boolean = false;
    resIds : number[] = [];

    @Input('injectDatas') injectDatas: any;

    @Output('reloadBadgeNotes') reloadBadgeNotes = new EventEmitter<string>();

    constructor(public http: HttpClient) { }

    ngOnInit(): void { }

    loadNotes(resId: number) {
        this.resIds[0] = resId;
        this.loading = true;
        this.http.get("../../rest/res/" + this.resIds[0] + "/notes")
        .subscribe((data: any) => {
            this.notes = data;
            this.reloadBadgeNotes.emit(`${this.notes.length}`);
            this.loading = false;
        });
    }
}