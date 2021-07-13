import { Component, AfterViewInit, Input, EventEmitter, Output } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../translate.component';
import { NotificationService } from '../notification.service';

@Component({
    selector: 'app-note-editor',
    templateUrl: 'note-editor.component.html',
    styleUrls: ['note-editor.component.scss'],
    providers: [NotificationService]
})
export class NoteEditorComponent implements AfterViewInit {

    lang: any = LANG;
    notes: any;
    loading: boolean = false;
    templatesNote: any = [];

    content: string = '';

    @Input('resIds') resIds: any[];
    @Input('addMode') addMode: boolean;
    @Output('refreshNotes') refreshNotes = new EventEmitter<string>();

    constructor(public http: HttpClient) { }

    ngAfterViewInit() {

    }

    addNote() {
        this.loading = true
        this.http.post("../../rest/res/" + this.resIds[0] + "/notes", { note_text: this.content })
            .subscribe((data: any) => {
                this.refreshNotes.emit(this.resIds[0]);
                this.loading = false;
            });
    }

    getNoteContent() {
        return this.content;
    }

    selectTemplate(template: any) {
        if (this.content.length > 0) {
            this.content = this.content + ' ' + template.template_content;
        } else {
            this.content = template.template_content;
        }

    }

    getTemplatesNote() {
        if (this.templatesNote.length == 0) {
            let params = {};
            if (this.resIds.length == 1) {
                params['resId'] = this.resIds[0];
            }
            this.http.get("../../rest/notes/templates", { params: params })
                .subscribe((data: any) => {
                    this.templatesNote = data['templates'];
                });

        }
    }
}