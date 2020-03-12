import { Component, Input, OnInit, EventEmitter, Output } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../translate.component';
import { NotificationService } from '../notification.service';
import { tap, finalize, catchError, exhaustMap, filter } from 'rxjs/operators';
import { of } from 'rxjs';
import { HeaderService } from '../../service/header.service';
import { ConfirmComponent } from '../../plugins/modal/confirm.component';
import { MatDialogRef, MatDialog } from '@angular/material';
import { FunctionsService } from '../../service/functions.service';

@Component({
    selector: 'app-notes-list',
    templateUrl: 'notes-list.component.html',
    styleUrls: ['notes-list.component.scss'],
    providers: [NotificationService]
})
export class NotesListComponent implements OnInit {

    lang: any = LANG;
    notes: any[] = [];
    loading: boolean = true;
    resIds : number[] = [];

    @Input('injectDatas') injectDatas: any;

    @Input('resId') resId: number = null;
    @Input('editMode') editMode: boolean = false;

    @Output('reloadBadgeNotes') reloadBadgeNotes = new EventEmitter<string>();

    dialogRef: MatDialogRef<any>;

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        private headerService: HeaderService,
        public dialog: MatDialog,
        public functions: FunctionsService
    ) { }

    ngOnInit(): void {
        if (this.resId !== null) {
            this.http.get(`../../rest/resources/${this.resId}/notes`).pipe(
                tap((data: any) => {
                    this.resIds[0] = this.resId;
                    this.notes = data['notes'];
                }),
                finalize(() => this.loading = false),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        }
    }

    loadNotes(resId: number) {
        this.resIds[0] = resId;
        this.loading = true;
        this.http.get("../../rest/resources/" + this.resIds[0] + "/notes")
        .subscribe((data: any) => {
            this.notes = data['notes'];
            this.reloadBadgeNotes.emit(`${this.notes.length}`);
            this.loading = false;
        });
    }

    getRestrictionEntitiesId(entities: any) {
        if (!this.functions.empty(entities)) {
            return entities.map((entity: any) => entity.item_id[0]);
        }
        return [];
    }

    removeNote(note: any) {
        this.dialogRef = this.dialog.open(ConfirmComponent, { panelClass: 'maarch-modal', autoFocus: false, disableClose: false, data: { title: this.lang.confirmRemoveNote, msg: this.lang.confirmAction } });

        this.dialogRef.afterClosed().pipe(
            filter((data: string) => data === 'ok'),
            exhaustMap(() => this.http.request('DELETE', '../../rest/notes/' + note.id)),
            tap(() => {
                var index = this.notes.findIndex(elem => elem.id == note.id)
                if (index > -1) {
                    this.notes.splice(index, 1);
                }
                this.notify.success(this.lang.noteRemoved);
                this.reloadBadgeNotes.emit(`${this.notes.length}`);
            })
        ).subscribe();
    }

    editNote(note: any) {
        if (!note.edit) {
            note.edit = true;
        } else {
            note.edit = false;
        }
    }
}
