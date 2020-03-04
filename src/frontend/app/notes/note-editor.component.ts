import { Component, Input, EventEmitter, Output, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../translate.component';
import { NotificationService } from '../notification.service';
import { catchError, tap, debounceTime, filter } from 'rxjs/operators';
import { HeaderService } from '../../service/header.service';
import { of } from 'rxjs';
import { FunctionsService } from '../../service/functions.service';
import { FormControl } from '@angular/forms';
import { LatinisePipe } from 'ngx-pipes';

@Component({
    selector: 'app-note-editor',
    templateUrl: 'note-editor.component.html',
    styleUrls: ['note-editor.component.scss'],
    providers: [NotificationService]
})
export class NoteEditorComponent implements OnInit {

    lang: any = LANG;
    notes: any;
    loading: boolean = false;
    templatesNote: any = [];
    entities: any = [];

    entitiesRestriction: string[] = [];

    @Input('title') title: string = this.lang.addNote;
    @Input('content') content: string = '';
    @Input('resIds') resIds: any[];
    @Input('addMode') addMode: boolean;
    @Input('upMode') upMode: boolean;
    @Input('noteContent') noteContent: string;
    @Input('entitiesNoteRestriction') entitiesNoteRestriction: string[];
    @Input('noteId') noteId: number;
    @Input('defaultRestriction') defaultRestriction: boolean;
    @Input('disableRestriction') disableRestriction: boolean = false;
    @Output('refreshNotes') refreshNotes = new EventEmitter<string>();

    searchTerm: FormControl = new FormControl();
    entitiesList: any[] = [];

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        public headerService: HeaderService,
        public functions: FunctionsService,
        private latinisePipe: LatinisePipe) { }

    async ngOnInit() {
        await this.getEntities();

        if (this.defaultRestriction) {
            this.setDefaultRestriction();
        }

        if (this.upMode) {
            this.content = this.noteContent;
            if (this.content.startsWith(`[${this.lang.avisUserState}]`) || this.content.startsWith(`[${this.lang.avisUserAsk.toUpperCase()}]`)) {
                this.disableRestriction = true;
            }
            this.entitiesRestriction = this.entitiesNoteRestriction;
        }

        this.entitiesList = this.entities;

        this.searchTerm.valueChanges.pipe(
            debounceTime(300),
            //distinctUntilChanged(),
            tap((data: any) => {
                if (data.length > 0) {
                    let filterValue = this.latinisePipe.transform(data.toLowerCase());
                    this.entitiesList = this.entities.filter( (item: any) => {
                        return (
                            this.latinisePipe.transform(item.entity_label.toLowerCase()).includes(filterValue) 
                                || this.latinisePipe.transform(item.entity_id.toLowerCase()).includes(filterValue)
                            );
                    });
                } else {
                    this.entitiesList = this.entities;
                }
            }),
            catchError((err) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    setDefaultRestriction() {
        this.entitiesRestriction = [];
        this.http.get(`../../rest/resources/${this.resIds[0]}/fields/destination`).pipe(
            tap((data: any) => {
                this.entitiesRestriction = this.headerService.user.entities.map((entity: any) => entity.entity_id);
                if (this.entitiesRestriction.indexOf(data.field) === -1 && !this.functions.empty(data.field)) {
                    this.entitiesRestriction.push(data.field);
                }
                this.entities.filter((entity: any) => this.entitiesRestriction.indexOf(entity.id) > -1).forEach((element: any) => {
                    element.selected = true;
                });
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    addNote() {
        this.loading = true;
        this.http.post("../../rest/notes", { value: this.content, resId: this.resIds[0], entities: this.entitiesRestriction })
            .subscribe((data: any) => {
                this.refreshNotes.emit(this.resIds[0]);
                this.loading = false;
            });
    }

    updateNote() {
        this.loading = true;
        this.http.put("../../rest/notes/" + this.noteId, { value: this.content, resId: this.resIds[0], entities: this.entitiesRestriction })
            .subscribe((data: any) => {
                this.refreshNotes.emit(this.resIds[0]);
                this.loading = false;
            });
    }

    getNoteContent() {
        return this.content;
    }

    setNoteContent(content: string) {
        this.content = content;
    }


    getNote() {
        return {content: this.content, entities: this.entitiesRestriction};
    }

    selectTemplate(template: any) {
        if (this.content.length > 0) {
            this.content = this.content + ' ' + template.template_content;
        } else {
            this.content = template.template_content;
        }
    }

    selectEntity(entity: any) {
        entity.selected = true;
        this.entitiesRestriction.push(entity.id);
    }

    getTemplatesNote() {
        if (this.templatesNote.length == 0) {
            let params = {};
            if (!this.functions.empty(this.resIds) && this.resIds.length == 1) {
                params['resId'] = this.resIds[0];
            }
            this.http.get("../../rest/notesTemplates", { params: params })
                .subscribe((data: any) => {
                    this.templatesNote = data['templates'];
                });

        }
    }

    getEntities() {
        return new Promise((resolve, reject) => {
            if (this.entities.length == 0) {
                let params = {};
                if (!this.functions.empty(this.resIds) && this.resIds.length == 1) {
                    params['resId'] = this.resIds[0];
                }
                this.http.get("../../rest/entities").pipe(
                    tap((data: any) => {
                        this.entities = data['entities'];
                        resolve(true);
                    }),
                    catchError((err: any) => {
                        this.notify.handleSoftErrors(err);
                        resolve(false);
                        return of(false);
                    })
                ).subscribe();

            }
        });
    }

    removeEntityRestriction(index: number, realIndex: number) {
        this.entities[realIndex].selected = false;
        this.entitiesRestriction.splice(index, 1);
    }
}
