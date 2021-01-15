import { Component, OnInit, Inject, ViewChild } from '@angular/core';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../notification.service';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material';
import { HttpClient } from '@angular/common/http';
import {NoteEditorComponent} from "../../notes/note-editor.component";

@Component({
    templateUrl: "update-departure-date-action.component.html",
    styleUrls: ['update-departure-date-action.component.scss'],
    providers: [NotificationService],
})
export class UpdateDepartureDateActionComponent implements OnInit {

    lang: any = LANG;
    loading: boolean = false;

    @ViewChild('noteEditor') noteEditor: NoteEditorComponent;

    constructor(public http: HttpClient, private notify: NotificationService, public dialogRef: MatDialogRef<UpdateDepartureDateActionComponent>, @Inject(MAT_DIALOG_DATA) public data: any) { }

    ngOnInit(): void { }

    onSubmit(): void {
        this.loading = true;
        this.http.put('../../rest/resourcesList/users/' + this.data.currentBasketInfo.ownerId + '/groups/' + this.data.currentBasketInfo.groupId + '/baskets/' + this.data.currentBasketInfo.basketId + '/actions/' + this.data.action.id, {resources : this.data.selectedRes, note : this.noteEditor.getNoteContent()})
            .subscribe((data: any) => {
                this.loading = false;
                this.dialogRef.close('success');
            }, (err: any) => {
                this.notify.handleErrors(err);
                this.loading = false;
            });
    }
}
